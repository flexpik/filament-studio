<?php

namespace Flexpik\FilamentStudio\Enums;

enum StudioPermission: string
{
    case ManageFields = 'studio.manageFields';
    case ManageApiKeys = 'studio.manageApiKeys';
    case ViewFlows = 'view_flows';
    case CreateFlows = 'create_flows';
    case UpdateFlows = 'update_flows';
    case DeleteFlows = 'delete_flows';
    case RunFlows = 'run_flows';
    case PublishFlow = 'publish_flow';
    case RunDangerousOperations = 'run_dangerous_operations';
    case ExportFlows = 'export_flows';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<int, string>
     */
    public static function collectionActions(): array
    {
        return ['viewRecords', 'createRecord', 'updateRecord', 'deleteRecord'];
    }

    /**
     * @return array<int, string>
     */
    public static function forCollection(string $slug): array
    {
        return array_map(
            fn (string $action) => "studio.collection.{$slug}.{$action}",
            self::collectionActions()
        );
    }

    /**
     * @return array<string, string>
     */
    public static function collectionPermissionLabels(string $slug): array
    {
        $labels = [
            'viewRecords' => 'View Records',
            'createRecord' => 'Create Record',
            'updateRecord' => 'Update Record',
            'deleteRecord' => 'Delete Record',
        ];

        $result = [];
        foreach ($labels as $action => $label) {
            $result["studio.collection.{$slug}.{$action}"] = $label;
        }

        return $result;
    }
}
