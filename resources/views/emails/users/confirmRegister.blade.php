<!DOCTYPE html>
<html lang="{{App::getLocale()}}">
    <head>
        <title>{{ Lang::get('mail.sign_in_confirmation')}}</title>
        <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">
    </head>
    <body>
        <div style="width: 100%; text-align:center; background-color:#fff; padding: 10px;">
            <img src="{{$message->embed(resource_path().'/images/logo.png')}}" alt="logo"
                style="max-width: 30%;">
            <hr>
            <div style="font-size: 20px;font-weight: bold;">
                <h2 style="text-transform: uppercase;">{{ Lang::get('mail.dear')}} {{$user['name']}}, </h2>
                <p>{{ Lang::get('mail.registration_succeeded')}} {{$appName}}.</p>
                <p>{{ Lang::get('mail.your_data_as_follow')}}:</p>
            </div>
            <hr>
            <table style="border: none">
                <tr>
                    <td><b>{{ Lang::get('mail.user_login')}}:</b></td>
                    <td>{{$user['email']}}</td>
                </tr>
                <tr>
                    <td><b>{{ Lang::get('mail.password')}}:</b></td>
                    <td>{{$user->getPasswordContainer()}}</td>
                </tr>
                <tr>
                    <td><a href="{{$url}}">{{ Lang::get('mail.click_to_access_the')}} {{$appName}}</a></td>
                </tr>
            </table>
        </div>
    </body>
</html>
