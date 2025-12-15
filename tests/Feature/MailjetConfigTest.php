<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MailjetConfigTest extends TestCase
{
    public function test_mailjet_config_is_loaded()
    {
        $this->assertNotEmpty(config('services.mailjet.key'));
        $this->assertNotEmpty(config('services.mailjet.secret'));
        $this->assertNotEmpty(config('services.mailjet.from_email'));

        $this->assertEquals('ee570f76f480562a5d762a4784ab06a9', config('services.mailjet.key'));
        $this->assertEquals('noresponder@votemanager.co', config('services.mailjet.from_email'));
    }
}
