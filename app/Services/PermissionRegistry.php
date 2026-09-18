<?php

namespace App\Services;

class PermissionRegistry
{
    /**
     * The 12 standard permission actions shown in the UI matrix.
     * Ordered row-by-row for 4-column display matching:
     * Col 1: View, Restore, Delete
     * Col 2: View Any, Restore Any, Delete Any
     * Col 3: Create, Replicate, Force Delete
     * Col 4: Update, Reorder, Force Delete Any
     */
    public static function getActions(): array
    {
        return [
            'view' => 'View',
            'view_any' => 'View Any',
            'create' => 'Create',
            'update' => 'Update',
            'restore' => 'Restore',
            'restore_any' => 'Restore Any',
            'replicate' => 'Replicate',
            'reorder' => 'Reorder',
            'delete' => 'Delete',
            'delete_any' => 'Delete Any',
            'force_delete' => 'Force Delete',
            'force_delete_any' => 'Force Delete Any',
        ];
    }

    /**
     * All system resources mapped to their model and permission prefix.
     */
    public static function getResources(): array
    {
        return [
            'purchase_requests' => [
                'label' => 'Purchase Request',
                'model' => 'App\\Modules\\Purchase\\Models\\PurchaseRequest',
                'prefix' => 'purchase-requests',
            ],
            'purchase_decisions' => [
                'label' => 'Purchase Decision',
                'model' => 'App\\Modules\\Purchase\\Models\\PurchaseDecision',
                'prefix' => 'purchase-decisions',
            ],
            'calculation_methods' => [
                'label' => 'Calculation Method',
                'model' => 'App\\Modules\\Core\\Calculation\\Models\\CalculationMethod',
                'prefix' => 'calculation-methods',
            ],
            'calculation_parameters' => [
                'label' => 'Calculation Parameter',
                'model' => 'App\\Modules\\Core\\Calculation\\Models\\CalculationParameter',
                'prefix' => 'calculation-parameters',
            ],
            'validation_rule_sets' => [
                'label' => 'Validation Rule Set',
                'model' => 'App\\Modules\\Core\\Validation\\Models\\ValidationRuleSet',
                'prefix' => 'validation-rule-sets',
            ],
            'checklists' => [
                'label' => 'Checklist',
                'model' => 'App\\Modules\\Core\\Workflow\\Models\\Checklist',
                'prefix' => 'checklists',
            ],
            'workflows' => [
                'label' => 'Workflow',
                'model' => 'App\\Modules\\Core\\Workflow\\Models\\Workflow',
                'prefix' => 'workflows',
            ],
            'users' => [
                'label' => 'User',
                'model' => 'App\\Models\\User',
                'prefix' => 'users',
            ],
            'roles' => [
                'label' => 'Role',
                'model' => 'Spatie\\Permission\\Models\\Role',
                'prefix' => 'roles',
            ],
            'permissions' => [
                'label' => 'Permission',
                'model' => 'Spatie\\Permission\\Models\\Permission',
                'prefix' => 'permissions',
            ],
            'branches' => [
                'label' => 'Branch',
                'model' => 'App\\Models\\Branch',
                'prefix' => 'branches',
            ],
            'categories' => [
                'label' => 'Category',
                'model' => 'App\\Models\\Category',
                'prefix' => 'categories',
            ],
            'sub_categories' => [
                'label' => 'Sub Category',
                'model' => 'App\\Models\\SubCategory',
                'prefix' => 'sub-categories',
            ],
            'products' => [
                'label' => 'Product',
                'model' => 'App\\Models\\Product',
                'prefix' => 'products',
            ],
            'product_types' => [
                'label' => 'Product Type',
                'model' => 'App\\Models\\ProductType',
                'prefix' => 'product-types',
            ],
            'locations' => [
                'label' => 'Location',
                'model' => 'App\\Models\\Location',
                'prefix' => 'locations',
            ],
            'product_checks' => [
                'label' => 'Product Check',
                'model' => 'App\\Models\\ProductCheck',
                'prefix' => 'product-checks',
            ],
            'deleted_product_checks' => [
                'label' => 'Deleted Product Check',
                'model' => 'App\\Models\\ProductCheck',
                'prefix' => 'deleted-product-checks',
            ],
            'sessions' => [
                'label' => 'Check Session',
                'model' => 'App\\Models\\CheckSession',
                'prefix' => 'sessions',
            ],
            'scan_configs' => [
                'label' => 'Scan Config',
                'model' => 'App\\Models\\ScanConfig',
                'prefix' => 'scan-configs',
            ],
            'decisions' => [
                'label' => 'Decision',
                'model' => 'App\\Models\\Decision',
                'prefix' => 'decisions',
            ],
            'decision_types' => [
                'label' => 'Decision Type',
                'model' => 'App\\Models\\DecisionType',
                'prefix' => 'decision-types',
            ],
            'decision_rules' => [
                'label' => 'Decision Rule',
                'model' => 'App\\Models\\DecisionRule',
                'prefix' => 'decision-rules',
            ],
            'product_import_batches' => [
                'label' => 'Product Import Batch',
                'model' => 'App\\Models\\ProductImportBatch',
                'prefix' => 'product-import-batches',
            ],
        ];
    }

    /**
     * Get all permission names generated for all resources.
     */
    public static function getAllResourcePermissionNames(): array
    {
        $permissions = [];
        $actions = array_keys(self::getActions());

        foreach (self::getResources() as $resource) {
            foreach ($actions as $action) {
                $permissions[] = "{$resource['prefix']}.{$action}";
            }
        }

        return $permissions;
    }
}
