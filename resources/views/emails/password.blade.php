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
            <div style="font-size: 20px;font-weight: bold;">
            {{ Lang::get('mail.password_reset_instructions')}}
            </div>
            <hr>
            <div>
            {{ Lang::get('mail.password_reset_link_instructions')}}:<br>
                <a href="{{ url('/#/password/reset/'.$token) }}">{{ url('/#/password/reset/'.$token) }}</a>
            </div>
        </div>
    </body>
</html>
