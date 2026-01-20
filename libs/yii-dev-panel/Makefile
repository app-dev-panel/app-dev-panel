install:
	docker run --rm -w /app -u `id -u`:`id -g` -v .:/app node:23.6.1 npm install

start:
	docker run --rm --name yii-dev-panel -w /app -u `id -u`:`id -g` -v .:/app -p 3000:3000 -p 3001:3001 node:23.6.1 node_modules/.bin/lerna run start --parallel --verbose -- --host=0.0.0.0
