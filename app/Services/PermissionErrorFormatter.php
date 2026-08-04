<?php

namespace App\Services;

use Illuminate\Support\Str;

class PermissionErrorFormatter
{
    /**
     * Formats a permission string or exception message into a human-readable 403 access error.
     * Example: "purchase-request.create" -> "You don't have access Purchase Request to Create"
     */
    public static function format(?string $message = null, ?\Throwable $exception = null): string
    {
        $rawPermission = self::resolveRawPermission($message, $exception);

        if (empty($rawPermission)) {
            return "This area is restricted. If you believe you should have access, please contact your administrator.";
        }

        // If message is in "resource.action" format (e.g. "purchase-request.create" or "purchase-requests.create")
        if (str_contains($rawPermission, '.')) {
            $parts = explode('.', $rawPermission, 2);
            $resourceStr = trim($parts[0]);
            $actionStr = trim($parts[1]);

            $resourceTitle = Str::title(str_replace(['-', '_'], ' ', $resourceStr));
            $resourceTitle = Str::singular($resourceTitle);
            $actionTitle = Str::title(str_replace(['-', '_'], ' ', $actionStr));

            return "You don't have access {$resourceTitle} to {$actionTitle}";
        }

        // If message is a single slug (e.g. "purchase-request-create")
        if (!str_contains($rawPermission, ' ') && (str_contains($rawPermission, '-') || str_contains($rawPermission, '_'))) {
            $title = Str::title(str_replace(['-', '_'], ' ', $rawPermission));
            return "You don't have access to {$title}";
        }

        // Return message as-is if it's already a descriptive sentence
        return $rawPermission;
    }

    /**
     * Resolves raw permission string from exception, message, or current HTTP request route path.
     */
    protected static function resolveRawPermission(?string $message, ?\Throwable $exception): ?string
    {
        // 1. Check if exception has Spatie getRequiredPermissions() method
        if ($exception && method_exists($exception, 'getRequiredPermissions')) {
            $permissions = $exception->getRequiredPermissions();
            if (!empty($permissions)) {
                return is_array($permissions) ? implode(', ', $permissions) : (string) $permissions;
            }
        }

        // 2. Extract permission from message if it contains bracket format: "... [purchase-request.create]"
        if (!empty($message) && preg_match('/\[([^\]]+)\]/', $message, $matches)) {
            return $matches[1];
        }

        // 3. If explicit non-default message is provided, use it
        if (!empty($message) && !self::isGenericLaravel403Message($message)) {
            return $message;
        }

        // 4. Infer from request route/URL if message is empty or generic
        return self::inferFromRequest();
    }

    protected static function isGenericLaravel403Message(string $msg): bool
    {
        $generic = [
            'this action is unauthorized.',
            'user does not have the right permissions.',
            'user does not have any of the necessary permissions.',
            'forbidden',
            '403 forbidden',
        ];

        return in_array(mb_strtolower(trim($msg)), $generic, true);
    }

    protected static function inferFromRequest(): ?string
    {
        if (!request()) {
            return null;
        }

        $path = trim(request()->getPathInfo(), '/');
        if (empty($path)) {
            return null;
        }

        $segments = array_values(array_filter(explode('/', $path)));

        // Strip known admin prefixes if present
        if (!empty($segments) && in_array($segments[0], ['admin', 'repurchase'], true)) {
            array_shift($segments);
        }

        if (empty($segments)) {
            return null;
        }

        $resource = $segments[0];
        $action = 'view';

        if (isset($segments[1])) {
            if ($segments[1] === 'create') {
                $action = 'create';
            } elseif (in_array($segments[1], ['edit', 'update'], true)) {
                $action = 'update';
            } elseif (isset($segments[2]) && in_array($segments[2], ['edit', 'update'], true)) {
                $action = 'update';
            }
        }

        return "{$resource}.{$action}";
    }
}
