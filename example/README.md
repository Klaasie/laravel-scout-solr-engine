# Laravel Scout Apache Solr example app

## Getting started

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

__Run the user seeder__
```
vendor/bin/sail artisan db:seed --class=DatabaseSeeder   
```

__Create the Solr indexes__
```
vendor/bin/sail artisan scout:index users
vendor/bin/sail artisan scout:index books 
```

__Import the users__
```
vendor/bin/sail artisan scout:import "App\Models\User"    
vendor/bin/sail artisan scout:import "App\Models\Book"    
```

Navigate to [localhost](http://localhost) to try the implementation.  
navigate to [solr1](http://localhost:8983) for the first solr instance containing the users index.  
navigate to [solr2](http://localhost:8984) for the second solr instance containing the books index.
