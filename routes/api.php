<?php

/**
 * API Routes Definition
 * Defines all available endpoints for the portfolio API
 */

// =====================================================
// HEALTH CHECK ROUTES
// =====================================================

// System Health Monitoring
$router->get('api/health', 'HealthController@index');
$router->get('api/health/status', 'HealthController@status');
$router->get('api/health/database', 'HealthController@database');

// =====================================================
// PUBLIC API ROUTES
// =====================================================

// Portfolio Data
$router->get('api/portfolio', 'PortfolioController@getAllData'); // Complete portfolio data
$router->get('api/portfolio/personal-info', 'PortfolioController@getPersonalInfo');
$router->get('api/portfolio/projects', 'PortfolioController@getProjects');
$router->get('api/portfolio/skills', 'PortfolioController@getSkills');
$router->get('api/portfolio/experience', 'PortfolioController@getExperience');
$router->get('api/portfolio/education', 'PortfolioController@getEducation');

// CV Generation and Download
$router->get('api/cv/download', 'CvController@download', ['RateLimit']);
$router->get('api/cv/stats', 'CvController@getStats');

// Contact Form Submission
$router->post('api/contact/submit', 'ContactController@submit', ['RateLimit']);

// =====================================================
// AUTHENTICATION ROUTES
// =====================================================

$router->post('api/auth/login', 'AuthController@login', ['RateLimit']);
$router->post('api/auth/logout', 'AuthController@logout', ['Auth']);
$router->get('api/auth/verify', 'AuthController@me', ['Auth']);

// =====================================================
// ADMIN API ROUTES (All require authentication)
// =====================================================

// Dashboard and Analytics
$router->get('api/admin/dashboard', 'AdminController@getDashboardStats', ['Auth']);

// Contact Messages Management
$router->get('api/admin/messages', 'AdminController@getMessages', ['Auth']);
$router->put('api/admin/messages/{id}/status', 'AdminController@updateMessageStatus', ['Auth']);
$router->delete('api/admin/messages/{id}', 'AdminController@deleteMessage', ['Auth']);

// Personal Information Management
$router->put('api/admin/personal-info', 'AdminController@updatePersonalInfo', ['Auth']);

// Projects Management
$router->get('api/admin/projects', 'AdminController@getAdminProjects', ['Auth']);
$router->post('api/admin/projects', 'AdminController@createProject', ['Auth']);
$router->put('api/admin/projects/{id}', 'AdminController@updateProject', ['Auth']);
$router->delete('api/admin/projects/{id}', 'AdminController@deleteProject', ['Auth']);

// Skills Management
$router->get('api/admin/skills', 'AdminController@getAdminSkills', ['Auth']);
$router->post('api/admin/skills', 'AdminController@createSkill', ['Auth']);
$router->put('api/admin/skills/{id}', 'AdminController@updateSkill', ['Auth']);
$router->delete('api/admin/skills/{id}', 'AdminController@deleteSkill', ['Auth']);

// Experience Management
$router->get('api/admin/experience', 'AdminController@getAdminExperience', ['Auth']);
$router->post('api/admin/experience', 'AdminController@createExperience', ['Auth']);
$router->put('api/admin/experience/{id}', 'AdminController@updateExperience', ['Auth']);
$router->delete('api/admin/experience/{id}', 'AdminController@deleteExperience', ['Auth']);

// Education Management (placeholder for future implementation)
$router->get('api/admin/education', 'AdminController@getAdminEducation', ['Auth']);
$router->post('api/admin/education', 'AdminController@createEducation', ['Auth']);
$router->put('api/admin/education/{id}', 'AdminController@updateEducation', ['Auth']);
$router->delete('api/admin/education/{id}', 'AdminController@deleteEducation', ['Auth']);

// =====================================================
// SYSTEM ROUTES
// =====================================================

// Health check endpoint
$router->get('api/health', function() {
    $response = \Core\Response::make();
    $response->json([
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0'
    ]);
});

// API documentation endpoint
$router->get('api', function() {
    $response = \Core\Response::make();
    $response->json([
        'name' => 'Netanel Klein Portfolio API',
        'version' => '1.0.0',
        'description' => 'RESTful API for portfolio website and admin panel',
        'documentation' => 'https://api.netanelk.com/docs',
        'endpoints' => [
            'portfolio' => [
                'GET /api/portfolio/personal-info',
                'GET /api/portfolio/projects',
                'GET /api/portfolio/skills',
                'GET /api/portfolio/experience',
                'GET /api/portfolio/education'
            ],
            'contact' => [
                'POST /api/contact/submit'
            ],
            'cv' => [
                'GET /api/cv/download'
            ],
            'auth' => [
                'POST /api/auth/login',
                'POST /api/auth/logout'
            ],
            'admin' => [
                'GET /api/admin/messages',
                'PUT /api/admin/personal-info',
                'POST|PUT|DELETE /api/admin/projects/{id}',
                'POST|PUT|DELETE /api/admin/skills/{id}',
                'POST|PUT|DELETE /api/admin/experience/{id}',
                'POST|PUT|DELETE /api/admin/education/{id}'
            ]
        ]
    ]);
});

// Add global middleware
$router->addGlobalMiddleware('Cors');
