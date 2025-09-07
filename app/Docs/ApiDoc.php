<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'Laravel 12 + Sanctum + Swagger',
    title: 'fluentlo API'
)]
#[OA\Server(url: '/api')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    bearerFormat: 'JWT',
    scheme: 'bearer'
)]
#[OA\Tag(name: 'Authentication', description: 'Auth endpoints')]
class ApiDoc
{

}
