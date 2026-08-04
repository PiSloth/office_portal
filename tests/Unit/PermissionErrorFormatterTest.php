<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PermissionErrorFormatter;

class PermissionErrorFormatterTest extends TestCase
{
    public function test_formats_dot_separated_permission_strings()
    {
        $result = PermissionErrorFormatter::format('purchase-request.create');
        $this->assertEquals("You don't have access Purchase Request to Create", $result);

        $resultPlural = PermissionErrorFormatter::format('purchase-requests.create');
        $this->assertEquals("You don't have access Purchase Request to Create", $resultPlural);

        $resultView = PermissionErrorFormatter::format('purchase-requests.view');
        $this->assertEquals("You don't have access Purchase Request to View", $resultView);

        $resultUpdate = PermissionErrorFormatter::format('calculation-methods.update');
        $this->assertEquals("You don't have access Calculation Method to Update", $resultUpdate);
    }

    public function test_formats_spatie_bracketed_permission_message()
    {
        $message = "User does not have the right permissions. [purchase-request.create]";
        $result = PermissionErrorFormatter::format($message);

        $this->assertEquals("You don't have access Purchase Request to Create", $result);
    }

    public function test_formats_single_slug_permissions()
    {
        $result = PermissionErrorFormatter::format('manage-settings');
        $this->assertEquals("You don't have access to Manage Settings", $result);
    }

    public function test_fallback_message_when_empty()
    {
        $result = PermissionErrorFormatter::format(null);
        $this->assertEquals("This area is restricted. If you believe you should have access, please contact your administrator.", $result);
    }
}
