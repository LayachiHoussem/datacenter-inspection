<?php
/**
 * Datacenter Inspection System - Root Front Controller Bridge
 * Ensures seamless compatibility with shared hosts (like InfinityFree / cPanel)
 * where the web root points to the repository root rather than /public.
 */

require_once __DIR__ . '/public/index.php';
