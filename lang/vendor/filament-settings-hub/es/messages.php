<?php

return [
    'title' => 'Ajustes',
    'group' => 'Ajustes',
    'back' => 'Atrás',
    'settings' => [
        'site' => [
            'title' => 'Ajustes del sitio',
            'description' => 'Administra los ajustes de tu sitio',
            'form' => [
                'site_name' => 'Nombre del sitio',
                'site_description' => 'Descripción del sitio',
                'site_logo' => 'Logo del sitio',
                'site_profile' => 'Imagen de perfil del sitio',
                'site_keywords' => 'Palabras clave del sitio',
                'site_email' => 'Correo electrónico del sitio',
                'site_phone' => 'Teléfono del sitio',
                'site_author' => 'Autor del sitio',
            ],
            'site-map' => 'Generar mapa del sitio',
            'site-map-notification' => 'Mapa del sitio generado exitosamente',
        ],
        'social' => [
            'title' => 'Menú social',
            'description' => 'Administra tu menú social',
            'form' => [
                'site_social' => 'Enlaces sociales',
                'vendor' => 'Vendor',
                'link' => 'Link',
            ],
        ],
        'location' => [
            'title' => 'Ajustes de ubicación',
            'description' => 'Administra los ajustes de ubicación',
            'form' => [
                'site_address' => 'Dirección del sitio',
                'site_phone_code' => 'Código de teléfono del sitio',
                'site_location' => 'Ubicación del sitio',
                'site_currency' => 'Moneda del sitio',
                'site_language' => 'Idioma del sitio',
            ],
        ],
    ],
];
