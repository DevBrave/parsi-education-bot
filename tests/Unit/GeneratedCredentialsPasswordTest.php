<?php

namespace Tests\Unit;

use App\Console\Commands\SetAdminCredentialsCommand;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class GeneratedCredentialsPasswordTest extends TestCase
{
    public function test_generated_passwords_have_ten_characters_and_all_character_groups(): void
    {
        $command = new SetAdminCredentialsCommand;
        $method = new ReflectionMethod($command, 'generatePassword');
        $passwords = [];
        for ($i = 0; $i < 100; $i++) {
            $password = $method->invoke($command);
            $this->assertSame(10, strlen($password));
            $this->assertMatchesRegularExpression('/[A-Z]/', $password);
            $this->assertMatchesRegularExpression('/[a-z]/', $password);
            $this->assertMatchesRegularExpression('/[0-9]/', $password);
            $this->assertMatchesRegularExpression('/[^a-zA-Z0-9]/', $password);
            $passwords[] = $password;
        }
        $this->assertCount(100, array_unique($passwords));
    }
}
