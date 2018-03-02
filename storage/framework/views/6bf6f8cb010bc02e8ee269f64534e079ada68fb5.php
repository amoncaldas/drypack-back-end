<!DOCTYPE html>
<html lang="<?php echo e(App::getLocale()); ?>">
    <head>
        <title><?php echo e(Lang::get('mail.email')); ?></title>
        <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">
    </head>
    <body>
        <div style="width: 100%; text-align:center; background-color:#fff; padding: 10px;">
            <img src="<?php echo e($message->embed(resource_path().'/images/logo.png')); ?>" alt="logo"
                style="max-width: 30%;">
            <hr>
            <div style="font-size: 20px;font-weight: bold;">
                <?php echo e($mailData['subject']); ?>

            </div>
            <hr>
            <div>
                <?php echo e($mailData['message']); ?>

            </div>
        </div>
    </body>
</html>
