<?php

namespace Tests\Unit;

use App\Support\ClaimMatcher;
use PHPUnit\Framework\TestCase;

class ClaimMatcherTest extends TestCase
{
    public function test_array_claims_match_any_element_exactly()
    {
        $claims = ['groups' => ['staff', 'RD01V78YL72JLKOZ']];

        $this->assertTrue(ClaimMatcher::matches($claims, 'groups', 'RD01V78YL72JLKOZ'));
        $this->assertFalse(ClaimMatcher::matches($claims, 'groups', 'RD01'));
    }

    public function test_string_claims_are_split_on_commas_and_whitespace()
    {
        $this->assertTrue(ClaimMatcher::matches(['roles' => 'viewer, admin'], 'roles', 'admin'));
        $this->assertTrue(ClaimMatcher::matches(['scope' => 'read write'], 'scope', 'write'));
        $this->assertTrue(ClaimMatcher::matches(['team' => 'badges'], 'team', 'badges'));
        $this->assertFalse(ClaimMatcher::matches(['roles' => 'notadmin'], 'roles', 'admin'));
    }

    public function test_nested_claims_use_dot_notation()
    {
        $claims = ['realm_access' => ['roles' => ['shifty-admin']]];

        $this->assertTrue(ClaimMatcher::matches($claims, 'realm_access.roles', 'shifty-admin'));
    }

    public function test_missing_or_non_scalar_claims_never_match()
    {
        $this->assertFalse(ClaimMatcher::matches([], 'groups', 'staff'));
        $this->assertFalse(ClaimMatcher::matches(['groups' => null], 'groups', 'staff'));
        $this->assertFalse(ClaimMatcher::matches(['groups' => [['staff']]], 'groups', 'staff'));
    }
}
