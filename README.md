# GimliDuck Devtools

WIP devtools for GimliDuck

composer require --dev danc0/gimliduck-devtools

Used with [GimliDuck PHP Framework](https://github.com/dvnc0/gimli-php)

```bash

       _           _ _ 
      (_)         | (_)
  __ _ _ _ __ ___ | |_ 
 / _` | | '_ ` _ \| | |
| (_| | | | | | | | | |
 \__, |_|_| |_| |_|_|_|
  __/ |                
 |___/                 
                                                               
-----------------------------------------------
✅ v0.1.0 👾 dvnc0

Devtools for GimliDuck Framework

|Command                       |Description                               |
|------------------------------|------------------------------------------|
|init                          |Create a devtools configuration file      |
|controller <controller_name>  |Create a new controller                   |
|logic <logic_name>            |Create a new logic file                   |
|model <model_name>            |Create a new model                        |
|job <job_name>                |Create a new Job                          |
|event <event_name>            |Create a new event                        |
|version                       |Prints the version information for gimli  |
```

Create the devtools config file with `vendor/bin/gimli init`

Use `vendor/bin/gimli help` to view commands and `vendor/bin/gimli <command> --help` for more information on a specific command.

## Current planned features:
- Create a new middleware
- Create a migration
- Create a route file
- Add a route to a route file
- Add a Vue file
- Create a skeleton project
- Run Vue build
- Run TailwindCSS build
- Run migrations