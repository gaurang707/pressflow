<?php

declare(strict_types=1);

namespace PressFlow\Lifecycle;

use PressFlow\Database\SchemaVerifier;

final class Activator
{
    private const DB_VERSION = '1.0.0';

    private const DB_VERSION_OPTION = 'pressflow_db_version';

    /**
     * Runs when the plugin is activated.
     */
    public static function activate(): void
    {
        global $wpdb;

        $schemaQueries = self::getSchemaQueries(
            $wpdb->get_charset_collate()
        );

        $tableNames = array_keys($schemaQueries);

        $installedVersion = (string) get_option(
            self::DB_VERSION_OPTION,
            ''
        );

        $missingTables = SchemaVerifier::missingTables(
            $tableNames
        );

        /*
         * Skip schema processing when the installed schema is current
         * and every required table exists.
         */
        if (
            self::DB_VERSION === $installedVersion
            && [] === $missingTables
        ) {
            update_option(
                'pressflow_plugin_version',
                PRESSFLOW_VERSION,
                false
            );

            return;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        /*
         * Creates missing tables and updates existing table structures.
         */
        dbDelta(array_values($schemaQueries));

        /*
         * Confirm that dbDelta successfully created every required table.
         */
        $missingTables = SchemaVerifier::missingTables(
            $tableNames
        );

        if ([] !== $missingTables) {
            wp_die(
                esc_html(
                    sprintf(
                        'PressFlow activation failed. Missing tables: %s',
                        implode(', ', $missingTables)
                    )
                ),
                esc_html__(
                    'PressFlow database verification failed',
                    'pressflow'
                ),
                ['back_link' => true]
            );
        }

        /*
         * Store versions only after successful schema verification.
         */
        update_option(
            self::DB_VERSION_OPTION,
            self::DB_VERSION,
            false
        );

        update_option(
            'pressflow_plugin_version',
            PRESSFLOW_VERSION,
            false
        );
    }

    /**
     * Returns table names and their CREATE TABLE queries.
     *
     * @return array<string, string>
     */
    private static function getSchemaQueries(
        string $charsetCollate
    ): array {
        global $wpdb;

        $contactsTable = $wpdb->prefix
            . 'pressflow_contacts';

        $listsTable = $wpdb->prefix
            . 'pressflow_lists';

        $listContactsTable = $wpdb->prefix
            . 'pressflow_list_contacts';

        $campaignsTable = $wpdb->prefix
            . 'pressflow_campaigns';

        $campaignRecipientsTable = $wpdb->prefix
            . 'pressflow_campaign_recipients';

        $eventsTable = $wpdb->prefix
            . 'pressflow_events';

        $auditLogsTable = $wpdb->prefix
            . 'pressflow_audit_logs';

        $queries = [];

        /*
         * Contacts table.
         */
        $queries[$contactsTable] = "CREATE TABLE {$contactsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(191) NOT NULL,
            first_name varchar(100) NULL,
            last_name varchar(100) NULL,
            publication varchar(190) NULL,
            country_code char(2) NULL,
            language_code varchar(10) NULL,
            topics_json longtext NULL,
            consent_status varchar(20) NOT NULL DEFAULT 'unknown',
            consent_source varchar(100) NULL,
            consent_at datetime NULL,
            subscription_status varchar(20) NOT NULL DEFAULT 'subscribed',
            last_engagement_at datetime NULL,
            created_by bigint(20) unsigned NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY email (email),
            KEY subscription_status (subscription_status),
            KEY consent_status (consent_status),
            KEY publication (publication),
            KEY country_language (country_code, language_code),
            KEY created_by (created_by)
            ) {$charsetCollate};";

        /*
         * Lists table.
         */
        $queries[$listsTable] = "CREATE TABLE {$listsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(190) NOT NULL,
            description text NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_by bigint(20) unsigned NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY created_by (created_by)
            ) {$charsetCollate};";

        /*
         * List-contact relationship table.
         */
        $queries[$listContactsTable] = "CREATE TABLE {$listContactsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            list_id bigint(20) unsigned NOT NULL,
            contact_id bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY list_contact (list_id, contact_id),
            KEY contact_id (contact_id)
            ) {$charsetCollate};";

        /*
         * Campaigns table.
         */
        $queries[$campaignsTable] = "CREATE TABLE {$campaignsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            release_id bigint(20) unsigned NOT NULL,
            name varchar(190) NOT NULL,
            subject varchar(255) NOT NULL,
            preview_text varchar(255) NULL,
            from_name varchar(190) NOT NULL,
            from_email varchar(191) NOT NULL,
            template_key varchar(100) NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'draft',
            scheduled_at datetime NULL,
            started_at datetime NULL,
            completed_at datetime NULL,
            cancelled_at datetime NULL,
            recipient_count int(10) unsigned NOT NULL DEFAULT 0,
            sent_count int(10) unsigned NOT NULL DEFAULT 0,
            failed_count int(10) unsigned NOT NULL DEFAULT 0,
            created_by bigint(20) unsigned NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status_schedule (status, scheduled_at),
            KEY release_id (release_id),
            KEY created_by (created_by)
            ) {$charsetCollate};";

        /*
         * Immutable campaign-recipient snapshot.
         */
        $queries[$campaignRecipientsTable]
            = "CREATE TABLE {$campaignRecipientsTable} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                campaign_id bigint(20) unsigned NOT NULL,
                contact_id bigint(20) unsigned NULL,
                recipient_key char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                email varchar(191) NOT NULL,
                first_name varchar(100) NULL,
                last_name varchar(100) NULL,
                publication varchar(190) NULL,
                status varchar(30) NOT NULL DEFAULT 'pending',
                attempts smallint(5) unsigned NOT NULL DEFAULT 0,
                available_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                claimed_at datetime NULL,
                sent_at datetime NULL,
                last_error_code varchar(100) NULL,
                last_error_message text NULL,
                open_count int(10) unsigned NOT NULL DEFAULT 0,
                click_count int(10) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY campaign_recipient (campaign_id, recipient_key),
                KEY campaign_status_available (campaign_id, status, available_at),
                KEY status_available (status, available_at),
                KEY contact_id (contact_id)
                ) {$charsetCollate};";

        /*
         * Events table.
         */
        $queries[$eventsTable] = "CREATE TABLE {$eventsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id bigint(20) unsigned NULL,
            campaign_id bigint(20) unsigned NULL,
            recipient_id bigint(20) unsigned NULL,
            visitor_hash char(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
            metadata_json longtext NULL,
            occurred_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY event_date (event_type, occurred_at),
            KEY object_event_date (object_type, object_id, event_type, occurred_at),
            KEY campaign_event_date (campaign_id, event_type, occurred_at),
            KEY recipient_event (recipient_id, event_type)
            ) {$charsetCollate};";

        /*
         * Audit logs table.
         */
        $queries[$auditLogsTable] = "CREATE TABLE {$auditLogsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
            action varchar(100) NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id bigint(20) unsigned NULL,
            request_id varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            context_json longtext NULL,
            ip_hash char(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY action_date (action, created_at),
            KEY actor_date (actor_id, created_at),
            KEY object_date (object_type, object_id, created_at),
            KEY request_id (request_id)
            ) {$charsetCollate};";

        return $queries;
    }
}
