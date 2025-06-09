<!DOCTYPE html>
<html lang="cz">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">
    <title>Reservation system - admin login</title>
   
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Roboto Mono", monospace;
        }

        body {
            background-color: #1e2124;
            color: #333;
            line-height: 1.6;
        }

        main {
            text-align: center;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        h2 {
            color: white;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .formsDiv {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 2rem;
        }

        form {
            border: none;
            border-radius: 12px;
            padding: 2rem;
            width: 350px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            background-color: #424549;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        form:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        label {
            display: block;
            text-align: left;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: white;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 1.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: black;
        }

        button {
            background-color: #1e2124;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 24px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        button:hover {
            background-color: #2980b9;
        }

        @media (max-width: 768px) {
            .formsDiv {
                flex-direction: column;
                align-items: center;
            }
            
            form {
                width: 90%;
                max-width: 350px;
            }
            
            h1 {
                font-size: 2rem;
            }
        }

        img {
            height: 5vh;
            width: auto;
        }

        #passwordMargin {
        margin-bottom: 9vh;
        }


    </style>
</head>
<body>
    <main>
        <h1>Reservation system</h1>
        
        <h2>You're logged in as ****, role: ****</h2>

    </main>
</body>
</html>