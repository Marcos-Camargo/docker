run:
	docker-compose.exe up -d
	
build:
	docker-compose.exe build
	docker-compose.exe down
	docker-compose.exe up -d
down:
	docker-compose.exe stop