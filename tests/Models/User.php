<?php

namespace Maize\LegalConsent\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Maize\LegalConsent\HasLegalConsent;

class User extends Authenticatable
{
    use HasFactory;
    use HasLegalConsent;
}
