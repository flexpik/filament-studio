import type { FieldSchema } from './FieldRenderer';

export const operationConfigSchemas: Record<string, { name: string; schema: FieldSchema }[]> = {
    log_message: [
        { name: 'level', schema: { type: 'enum', label: 'Level', options: ['debug', 'info', 'warning', 'error'] } },
        { name: 'message', schema: { type: 'text', label: 'Message' } },
    ],
    send_email: [
        { name: 'to', schema: { type: 'string', label: 'To' } },
        { name: 'subject', schema: { type: 'string', label: 'Subject' } },
        { name: 'body', schema: { type: 'text', label: 'Body (HTML)' } },
        { name: 'cc', schema: { type: 'string', label: 'CC' } },
        { name: 'reply_to', schema: { type: 'string', label: 'Reply-To' } },
    ],
    http_request: [
        { name: 'method', schema: { type: 'enum', label: 'Method', options: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] } },
        { name: 'url', schema: { type: 'string', label: 'URL' } },
        { name: 'headers', schema: { type: 'key_value', label: 'Headers' } },
        { name: 'body', schema: { type: 'json', label: 'Body' } },
        { name: 'fail_on_error', schema: { type: 'boolean', label: 'Fail on 4xx/5xx' } },
    ],
    create_record: [
        { name: 'collection', schema: { type: 'collection_select', label: 'Collection', options: [] } },
        { name: 'data', schema: { type: 'json', label: 'Data' } },
    ],
    read_record: [
        { name: 'collection', schema: { type: 'collection_select', label: 'Collection', options: [] } },
        { name: 'id', schema: { type: 'string', label: 'Record ID' } },
    ],
    update_record: [
        { name: 'collection', schema: { type: 'collection_select', label: 'Collection', options: [] } },
        { name: 'id', schema: { type: 'string', label: 'Record ID' } },
        { name: 'data', schema: { type: 'json', label: 'Data' } },
    ],
    delete_record: [
        { name: 'collection', schema: { type: 'collection_select', label: 'Collection', options: [] } },
        { name: 'id', schema: { type: 'string', label: 'Record ID' } },
    ],
    transform_payload: [
        { name: 'payload', schema: { type: 'json', label: 'Payload' } },
    ],
    condition: [
        { name: 'filter', schema: { type: 'json', label: 'Filter (rule tree)' } },
    ],
    trigger_flow: [
        { name: 'flow_id', schema: { type: 'flow_select', label: 'Flow', options: [] } },
        { name: 'mode', schema: { type: 'enum', label: 'Mode', options: ['async', 'sync'] } },
        { name: 'payload', schema: { type: 'json', label: 'Payload' } },
    ],
};

export const triggerConfigSchemas: Record<string, { name: string; schema: FieldSchema }[]> = {
    manual: [],
    webhook: [
        { name: 'auth_mode', schema: { type: 'enum', label: 'Auth', options: ['none', 'bearer', 'hmac'] } },
        { name: 'response_mode', schema: { type: 'enum', label: 'Response', options: ['async', 'sync'] } },
    ],
    collection_event: [
        { name: 'collection', schema: { type: 'collection_select', label: 'Collection', options: [] } },
        { name: 'events', schema: { type: 'json', label: 'Events (array)' } },
    ],
    schedule: [
        { name: 'cron', schema: { type: 'string', label: 'CRON expression' } },
        { name: 'timezone', schema: { type: 'string', label: 'Timezone' } },
    ],
};
