<?php

namespace App\Domain\Documents\Enums;

enum Visibility: string
{
    case Public = 'public';
    case Private = 'private';
}
