<!DOCTYPE html>
<html lang="{{App::getLocale()}}">
    <head>
        <title>{{ Lang::get('mail.password_reset')}}</title>
        <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">
    </head>
    <body>
        <div style="width: 100%; text-align:center; background-color:#fff; padding: 10px;">
            <img src="{{$message->embed(public_path().'/admin/images/logo.png')}}" alt="logo"
                style="max-width: 30%;">
            <hr>
            <div style="font-size: 12px;">
                <h2 style="text-transform: uppercase;">{{ Lang::get('mail.dear')}} {{$user['name']}}, </h2>
                <p>{{ Lang::get('mail.link_to_reset_your_password_in_the')}} {{$appName}}.</p>
                <p>{{ Lang::get('mail.if_not_requested_pass_reset')}}.</p>
                <p>{{ Lang::get('mail.reset_link_validity')}}.</p>
                <p>{{ Lang::get('mail.password_reset_link_instructions')}}.</p>
                <p><a href="{{ url('/admin/#/password/reset/'.$token) }}">{{ url('/admin/#/password/reset/'.$token) }}</a></p>
            </div>
        </div>
    </body>
</html>
