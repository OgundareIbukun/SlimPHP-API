<?php

if ($mode == 'production' || $mode == 'debug') {
    // HOUSGIRL API ROUTES
    $app->group('/api', function () use ($app) {
        $app->group('/v1', function () use ($app) {

            // admin routes
            $app->post('/sms/admin', 'AdminController:createAdmin');
            $app->put('/sms/admin/{email}', 'AdminController:updateAdmin');
            $app->get('/sms/admin', 'AdminController:getAllAdmin');
            $app->get('/sms/admin/{email}', 'AdminController:getSingleAdmin');
            $app->get('/sms/admin/verify/{email}/{token}', 'AdminController:verifyEmailAndToken');
            $app->delete('/sms/admin/{email}', 'AdminController:deleteAdmin');

            //Student routes
            $app->post('/sms/students', 'StudentController:createStudent');
            $app->put('/sms/students/{email}', 'StudentController:updateStudent');  
            $app->put('/sms/students/changepass/{email}', 'StudentController:changepass');
            $app->get('/sms/students', 'StudentController:getAllStudent');
            $app->get('/sms/students/{email}', 'StudentController:getSingleStudent');
            $app->get('/sms/students/verify/{email}/{token}', 'StudentController:verifyEmailAndToken');
            $app->get('/sms/students/isverify/{email}', 'StudentController:verifyEmail');
            $app->delete('/sms/students/{email}', 'StudentController:deleteStudent');


            // users routes
            $app->post('/accounts/users', 'UserController:createUser');
            $app->put('/accounts/users/{email}', 'UserController:updateUser');  
            $app->put('/accounts/users/changepass/{email}', 'UserController:changepass');
            $app->get('/accounts/users', 'UserController:getAllUser');
            $app->get('/accounts/users/{email}', 'UserController:getSingleUser');
            $app->get('/accounts/users/verify/{email}/{token}', 'UserController:verifyEmailAndToken');
            $app->get('/accounts/users/isverify/{email}', 'UserController:verifyEmail');
            $app->delete('/accounts/users/{email}', 'UserController:deleteUser');

            // merchant routes
            $app->post('/accounts/merchant', 'MerchantController:createMerchant');
            $app->get('/accounts/merchant', 'MerchantController:getAllMerchant');
            $app->get('/accounts/merchant/{email}', 'MerchantController:getSingleMerchant');
            $app->get('/accounts/merchant/verify/{email}/{token}', 'MerchantController:verifyEmailAndToken');
            $app->put('/accounts/merchant/{email}', 'MerchantController:updateMerchant');
            $app->delete('/accounts/merchant/{email}', 'MerchantController:deleteMerchant');
        });
    });
}

//uoVrQcnsLZmHeGuiCcGoTwZjLHfqpEUd