<?php

namespace App\Data;

use App\Data\Casts\RepositoryNameCast;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;

class ReleaseData extends Data
{
    #[MapInputName('repository.owner.login')]
    public string $organization;

    #[MapInputName('repository.name')]
    #[WithCast(RepositoryNameCast::class)]
    public string $repository;

    #[MapInputName('release.name')]
    public string $version;

    #[MapInputName('release.html_url')]
    public string $url;

    #[MapInputName('release.body')]
    public string $changelog;
}
