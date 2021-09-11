## Laravel Scout Apache Solr example app

### Getting started

Provided in this example app is a sail docker-compose file with Solr.
Execute the following list of commands to get this app ready to go:

__Get containers up__
```
vendor/bin/sail up
```

__Migrate database__
```
vendor/bin/sail artisan migrate
```

__Run the user seeder___
```
vendor/bin/sail artisan db:seed --class=DatabaseSeeder   
```

__Create the Solr index__
```
vendor/bin/sail artisan scout:index users   
```

__Import the users__
```
vendor/bin/sail artisan scout:import "App\Models\User"    
```

Navigate to [localhost](http://localhost) to try the implementation.
