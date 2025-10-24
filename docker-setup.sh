#!/bin/bash

echo "🐳 Setting up ERPSAAS with Docker..."

# Build and start containers
echo "📦 Building Docker containers..."
docker-compose build

echo "🚀 Starting Docker containers..."
docker-compose up -d

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
sleep 10

# Install composer dependencies
echo "📚 Installing Composer dependencies..."
docker-compose exec -T app composer install

# Generate application key
echo "🔑 Generating application key..."
docker-compose exec -T app php artisan key:generate

# Run migrations
echo "🗄️  Running database migrations..."
docker-compose exec -T app php artisan migrate

# Build Filament assets
echo "🎨 Building Filament assets..."
docker-compose exec -T app php artisan filament:assets

# Run npm build (this will be handled by the npm container)
echo "🎨 Building frontend assets..."
docker-compose up npm

# Seed database (optional)
read -p "Do you want to seed the database? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]
then
    echo "🌱 Seeding database..."
    docker-compose exec -T app php artisan db:seed
fi

echo "✅ Setup complete!"
echo ""
echo "📝 Default login credentials:"
echo "   Email: admin@erpsaas.com"
echo "   Password: password"
echo ""
echo "🌐 Application is running at: http://localhost:8080"
echo ""
echo "📌 Useful commands:"
echo "   docker-compose logs -f app      # View application logs"
echo "   docker-compose exec app bash    # Access app container shell"
echo "   docker-compose down             # Stop all containers"
echo "   docker-compose up -d            # Start all containers"
