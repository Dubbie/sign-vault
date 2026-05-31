<?php

namespace App\Enums;

enum FolderVisibility: string
{
    case Private = 'private';
    case Public = 'public';
    case Password = 'password';
}
