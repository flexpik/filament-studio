<?php

namespace Flexpik\FilamentStudio\Api\OpenApi;

use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Path;
use Dedoc\Scramble\Support\Generator\RequestBodyObject;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\NumberType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\Type;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Services\LocaleResolver;
use Illuminate\Support\Collection;

class StudioDocumentTransformer
{
    public function __invoke(OpenApi $openApi): void
    {
        $openApi->secure(
            SecurityScheme::apiKey('header', 'X-Api-Key')
        );

        $prefix = config('filament-studio.api.prefix', 'api/studio');

        // Scramble sets the server URL to {APP_URL}/{api_path}, so paths in the
        // OpenAPI spec must be relative to that base. Strip the api_path prefix
        // to avoid doubling (e.g. /api/api/studio/...).
        $apiPath = config('scramble.api_path', 'api');
        $docPrefix = preg_replace('#^'.preg_quote($apiPath, '#').'/?#', '', $prefix);

        $collections = StudioCollection::query()
            ->apiEnabled()
            ->with(['fields' => fn ($q) => $q->where('is_system', false)->orderBy('sort_order')])
            ->get();

        foreach ($collections as $collection) {
            $this->addCollectionPaths($openApi, $collection, $docPrefix);
        }

        // Remove generic wildcard paths that Scramble auto-discovered
        $openApi->paths = array_values(array_filter(
            $openApi->paths,
            fn (Path $path) => ! str_contains($path->path, '{collection_slug}'),
        ));
    }

    protected function addCollectionPaths(OpenApi $openApi, StudioCollection $collection, string $prefix): void
    {
        $tag = $collection->label_plural;
        $slug = $collection->slug;
        $fields = $collection->fields;

        // List + Create path: /api/studio/{slug}
        $listPath = Path::make("{$prefix}/{$slug}");
        $listPath->addOperation($this->buildIndexOperation($collection, $tag));
        $listPath->addOperation($this->buildStoreOperation($collection, $fields, $tag));
        $openApi->addPath($listPath);

        // Show + Update + Delete path: /api/studio/{slug}/{uuid}
        $itemPath = Path::make("{$prefix}/{$slug}/{uuid}");
        $itemPath->addOperation($this->buildShowOperation($collection, $tag));
        $itemPath->addOperation($this->buildUpdateOperation($collection, $fields, $tag));
        $itemPath->addOperation($this->buildDestroyOperation($collection, $tag));
        $openApi->addPath($itemPath);
    }

    protected function buildIndexOperation(StudioCollection $collection, string $tag): Operation
    {
        return Operation::make('get')
            ->setOperationId("list{$this->pascalCase($collection->slug)}")
            ->summary("List all {$collection->label_plural}")
            ->description(($collection->description ?? "Retrieve a paginated list of {$collection->label_plural}.").$this->localeDescriptionSuffix($collection))
            ->setTags([$tag])
            ->addParameters([
                (new Parameter('X-Api-Key', 'header'))
                    ->description('API key for authentication')
                    ->required(true)
                    ->setSchema(Schema::fromType(new StringType)),
                (new Parameter('per_page', 'query'))
                    ->description('Number of records per page (max 100)')
                    ->setSchema(Schema::fromType((new IntegerType)->default(25))),
                (new Parameter('page', 'query'))
                    ->description('Page number')
                    ->setSchema(Schema::fromType((new IntegerType)->default(1))),
            ])
            ->addParameters($this->buildLocaleParameters($collection))
            ->addResponse(
                Response::make(200)
                    ->setDescription("Paginated list of {$collection->label_plural}")
                    ->setContent('application/json', Schema::fromType($this->buildPaginatedResponseType($collection)))
            );
    }

    protected function buildShowOperation(StudioCollection $collection, string $tag): Operation
    {
        return Operation::make('get')
            ->setOperationId("show{$this->pascalCase($collection->slug)}")
            ->summary("Get a single {$collection->label}")
            ->description("Retrieve a single {$collection->label} record by UUID.".$this->localeDescriptionSuffix($collection))
            ->setTags([$tag])
            ->addParameters([
                (new Parameter('X-Api-Key', 'header'))
                    ->description('API key for authentication')
                    ->required(true)
                    ->setSchema(Schema::fromType(new StringType)),
                (new Parameter('uuid', 'path'))
                    ->description("The UUID of the {$collection->label}")
                    ->required(true)
                    ->setSchema(Schema::fromType(new StringType)),
            ])
            ->addParameters($this->buildLocaleParameters($collection, includeAllLocales: true))
            ->addResponse(
                Response::make(200)
                    ->setDescription("{$collection->label} record")
                    ->setContent('application/json', Schema::fromType($this->buildRecordResponseType($collection)))
            )
            ->addResponse(
                Response::make(404)->setDescription("{$collection->label} not found")
            );
    }

    protected function buildStoreOperation(StudioCollection $collection, Collection $fields, string $tag): Operation
    {
        $operation = Operation::make('post')
            ->setOperationId("create{$this->pascalCase($collection->slug)}")
            ->summary("Create a new {$collection->label}")
            ->description("Create a new {$collection->label} record.".$this->localeDescriptionSuffix($collection))
            ->setTags([$tag])
            ->addParameters([
                (new Parameter('X-Api-Key', 'header'))
                    ->description('API key for authentication')
                    ->required(true)
                    ->setSchema(Schema::fromType(new StringType)),
            ])
            ->addParameters($this->buildLocaleParameters($collection))
            ->addResponse(
                Response::make(201)
                    ->setDescription("{$collection->label} created")
                    ->setContent('application/json', Schema::fromType($this->buildRecordResponseType($collection)))
            )
            ->addResponse(
                Response::make(422)->setDescription('Validation error')
            );

        if ($fields->isNotEmpty()) {
            $operation->addRequestBodyObject(
                RequestBodyObject::make()
                    ->required(true)
                    ->setContent('application/json', Schema::fromType(
                        $this->buildRequestBodyType($fields, isStore: true)
                    ))
            );
        }

        return $operation;
    }

    protected function buildUpdateOperation(StudioCollection $collection, Collection $fields, string $tag): Operation
    {
        $operation = Operation::make('put')
            ->setOperationId("update{$this->pascalCase($collection->slug)}")
            ->summary("Update a {$collection->label}")
            ->description("Update an existing {$collection->label} record.".$this->localeDescriptionSuffix($collection))
            ->setTags([$tag])
            ->addParameters([
                (new Parameter('X-Api-Key', 'header'))
                    ->description('API key for authentication')
                    ->required(true)
                    ->setSchema(Schema::fromType(new StringType)),
                (new Parameter('uuid', 'path'))
                    ->description("The UUID of the {$collection->label}")
                    ->required(true)
                    ->setSchema(Schema::fromType(new StringType)),
            ])
            ->addParameters($this->buildLocaleParameters($collection))
            ->addResponse(
                Response::make(200)
                    ->setDescription("{$collection->label} updated")
                    ->setContent('application/json', Schema::fromType($this->buildRecordResponseType($collection)))
            )
            ->addResponse(
                Response::make(404)->setDescription("{$collection->label} not found")
            )
            ->addResponse(
                Response::make(422)->setDescription('Validation error')
            );

        if ($fields->isNotEmpty()) {
            $operation->addRequestBodyObject(
                RequestBodyObject::make()
                    ->required(true)
                    ->setContent('application/json', Schema::fromType(
                        $this->buildRequestBodyType($fields, isStore: false)
                    ))
            );
        }

        return $operation;
    }

    protected function buildDestroyOperation(StudioCollection $collection, string $tag): Operation
    {
        return Operation::make('delete')
            ->setOperationId("delete{$this->pascalCase($collection->slug)}")
            ->summary("Delete a {$collection->label}")
            ->description("Delete a {$collection->label} record by UUID.")
            ->setTags([$tag])
            ->addParameters([
                (new Parameter('X-Api-Key', 'header'))
                    ->description('API key for authentication')
                    ->required(true)
                    ->setSchema(Schema::fromType(new StringType)),
                (new Parameter('uuid', 'path'))
                    ->description("The UUID of the {$collection->label}")
                    ->required(true)
                    ->setSchema(Schema::fromType(new StringType)),
            ])
            ->addResponse(
                Response::make(204)->setDescription("{$collection->label} deleted")
            )
            ->addResponse(
                Response::make(404)->setDescription("{$collection->label} not found")
            );
    }

    /**
     * @param  Collection<int, StudioField>  $fields
     */
    protected function buildRequestBodyType(Collection $fields, bool $isStore): ObjectType
    {
        $dataType = new ObjectType;
        $required = [];

        /** @var StudioField $field */
        foreach ($fields as $field) {
            $fieldType = $this->eavCastToOpenApiType($field->eav_cast->value);
            $fieldType->setDescription($field->hint ?? $field->label);

            $dataType->addProperty($field->column_name, $fieldType);

            if ($isStore && $field->is_required) {
                $required[] = $field->column_name;
            }
        }

        if (! empty($required)) {
            $dataType->setRequired($required);
        }

        $wrapper = new ObjectType;
        $wrapper->addProperty('data', $dataType);

        return $wrapper;
    }

    protected function buildRecordResponseType(StudioCollection $collection): ObjectType
    {
        $dataType = new ObjectType;

        /** @var StudioField $field */
        foreach ($collection->fields as $field) {
            $dataType->addProperty(
                $field->column_name,
                $this->eavCastToOpenApiType($field->eav_cast->value)
            );
        }

        $recordType = new ObjectType;
        $recordType->addProperty('uuid', new StringType);
        $recordType->addProperty('data', $dataType);
        $recordType->addProperty('created_by', (new IntegerType)->nullable(true));
        $recordType->addProperty('updated_by', (new IntegerType)->nullable(true));
        $recordType->addProperty('created_at', (new StringType)->format('date-time'));
        $recordType->addProperty('updated_at', (new StringType)->format('date-time'));

        $wrapper = new ObjectType;
        $wrapper->addProperty('data', $recordType);

        if (config('filament-studio.locales.enabled', false)) {
            $metaType = new ObjectType;
            $metaType->addProperty('locale', (new StringType)->setDescription('The active locale used for this response'));
            $metaType->addProperty('fallbacks', (new ArrayType)->setItems(new StringType)->setDescription('Field names that fell back to the default locale'));

            $wrapper->addProperty('_meta', $metaType);
        }

        return $wrapper;
    }

    protected function buildPaginatedResponseType(StudioCollection $collection): ObjectType
    {
        $recordType = $this->buildRecordResponseType($collection);
        $innerRecord = $recordType->properties['data'];

        $arrayType = new ArrayType;
        $arrayType->setItems($innerRecord);

        $linksType = new ObjectType;
        $linksType->addProperty('first', (new StringType)->nullable(true));
        $linksType->addProperty('last', (new StringType)->nullable(true));
        $linksType->addProperty('prev', (new StringType)->nullable(true));
        $linksType->addProperty('next', (new StringType)->nullable(true));

        $metaType = new ObjectType;
        $metaType->addProperty('current_page', new IntegerType);
        $metaType->addProperty('last_page', new IntegerType);
        $metaType->addProperty('per_page', new IntegerType);
        $metaType->addProperty('total', new IntegerType);

        $paginatedType = new ObjectType;
        $paginatedType->addProperty('data', $arrayType);
        $paginatedType->addProperty('links', $linksType);
        $paginatedType->addProperty('meta', $metaType);

        return $paginatedType;
    }

    protected function eavCastToOpenApiType(string $cast): Type
    {
        return match ($cast) {
            'text' => new StringType,
            'integer' => new IntegerType,
            'decimal' => new NumberType,
            'boolean' => new BooleanType,
            'datetime' => (new StringType)->format('date-time'),
            'json' => new ObjectType,
            default => new StringType,
        };
    }

    /**
     * Build locale-related OpenAPI parameters for a collection.
     *
     * @return array<Parameter>
     */
    protected function buildLocaleParameters(StudioCollection $collection, bool $includeAllLocales = false): array
    {
        if (! config('filament-studio.locales.enabled', false)) {
            return [];
        }

        $resolver = app(LocaleResolver::class);
        $available = $resolver->availableLocales($collection);
        $default = $resolver->defaultLocale($collection);

        $localeEnum = new StringType;
        $localeEnum->enum($available);
        $localeEnum->default($default);

        $params = [
            (new Parameter('locale', 'query'))
                ->description('Locale for translatable fields. Available: '.implode(', ', $available))
                ->setSchema(Schema::fromType($localeEnum)),
            (new Parameter('X-Locale', 'header'))
                ->description('Locale for translatable fields (alternative to query param). Available: '.implode(', ', $available))
                ->setSchema(Schema::fromType((clone $localeEnum))),
        ];

        if ($includeAllLocales) {
            $params[] = (new Parameter('all_locales', 'query'))
                ->description('When true, returns all locale variants for translatable fields as nested objects')
                ->setSchema(Schema::fromType((new BooleanType)->default(false)));
        }

        return $params;
    }

    protected function localeDescriptionSuffix(StudioCollection $collection): string
    {
        if (! config('filament-studio.locales.enabled', false)) {
            return '';
        }

        $resolver = app(LocaleResolver::class);
        $available = $resolver->availableLocales($collection);

        return "\n\nSupports multilingual content. Use `locale` query parameter or `X-Locale` header to select a locale. Available locales: ".implode(', ', $available).'.';
    }

    protected function pascalCase(string $slug): string
    {
        return str($slug)->studly()->toString();
    }
}
