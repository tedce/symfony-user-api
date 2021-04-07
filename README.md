## Symfony User API ##

### Setup ###

##### Clone Repo #####

    git clone https://github.com/tedce/symfony-user-api.git
    cd symfony-user-api
    
##### Install dependencies #####
     
     composer install
     
##### Create docker network with subnet (this is used in docker-compose.yml file) #####

    docker network create --subnet 10.5.0.0/24 symfony
    
##### Bring docker-compose environment up #####

    docker-compose up --build
    
##### Migrate the database #####

    bin/console doctrine:migrations:migrate
    bin/console doctrine:schema:update --force
    
##### Navigate to docs and start testing calls! #####

    http://localhost:8000/api/doc
     