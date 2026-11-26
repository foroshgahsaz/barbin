<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super_admin';
    case WarehouseManager = 'warehouse_manager';
    case SalesManager = 'sales_manager';
    case SalesOperator = 'sales_operator';
    case OrganizationViewer = 'organization_viewer';
}