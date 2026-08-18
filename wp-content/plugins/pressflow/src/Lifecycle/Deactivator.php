<?php

declare(strict_types=1);

namespace PressFlow\Lifecycle;

final class Deactivator
{
    public static function deactivate(): void
    {
        // Reserved for future deactivation requirements.
        delete_transient('pressflow_foundation_status');

        // Remove cached rewrite rules.
        flush_rewrite_rules(false);

        // Never delete tables, options, contacts,
        // campaigns or WordPress content here.
    }
}
