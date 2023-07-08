<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        body {
            background-color: #30444F;
            margin: 0;
            padding: 0;
        }

        h1 {
            color: #E7D31F;
            font-family: "Poppins", sans-serif;
            text-align: center;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        ul {
            text-align: center;
            color: red;
        }

        form {
            text-align: center;
        }

        .logo {
            max-width: 200px;
        }

        input[type="password"] {
            padding: 10px;
            width: 300px;
        }
    </style>
    <link href="/assets/font/Poppins/Poppins-Regular.ttf" rel="stylesheet">
    <title>Reset Password Page</title>
</head>
<body>
    <div class="container">
        
        <h1>Reset Password Page</h1>
        <img src="https://img.icons8.com/?size=512&id=0YOQOBnG7JCM&format=png" alt="Logo" class="logo">
        @if($errors->any())
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST">
            @csrf
            <input type="hidden" name="id" value="{{ $user[0]['id'] }}">
            <input type="password" name="password" placeholder="New Password">
            <br><br>
            <input type="password" name="password_confirmation" placeholder="Confirm Password">
            <br><br>
            <input type="submit">
        </form>
    </div>
</body>
</html>
