## Laravel Crypto Wallet API

### Overview
The Laravel Crypto Wallet API is a simple RESTful API that allows users to manage crypto accounts for multiple coins such as Bitcoin, Ethereum, and Litecoin. It provides endpoints to view user balances, transfer funds between users, list transactions, and convert balances between different cryptocurrencies using live market rates.

### Installation Instructions
To set up the Laravel Crypto Wallet API on your local environment, follow these steps:

1. Clone the repository:
    
    git clone <repository_url>
    

2. Navigate to the project directory:
    
    cd laravel-crypto-wallet-api
    

3. Install PHP dependencies using Composer:
    
    composer install
    

4. Copy the `.env.example` file and rename it to `.env`. Update the database connection details and other necessary configurations in the `.env` file.

5. Generate an application key:
    
    php artisan key:generate
    

6. Run database migrations to create tables:
    
    php artisan migrate
    

7. Start the development server:
    
    php artisan serve


8. To check the documentation    
     
    localhost:8000/doc.html

### Design and Assumptions
The project follows a simple design where users can manage their crypto accounts and perform transactions. Here are some design decisions and assumptions:

- **User Authentication**: The API uses Laravel Sanctum for API authentication. Users can register using their phone and password and obtain an access token to access protected endpoints.

- **Crypto Accounts**: Each user can have multiple crypto accounts for different coins. The system supports Bitcoin, Ethereum, and Litecoin by default, but it can be easily extended to support more cryptocurrencies.

- **Transactions**: Transactions are recorded for each crypto account. The API provides endpoints to transfer funds between users, listing transactions, and converting balances between different cryptocurrencies using live market rates.

- **Market Price**: The API leverages a publicly available API such as CoinGecko to fetch live market prices for cryptocurrencies. This ensures that conversions are based on accurate market rates.

- **Error Handling**: The API handles validation errors and exceptions gracefully, returning appropriate HTTP status codes and error messages in JSON format.

- **Assumptions**: The project assumes basic knowledge of Laravel framework and PHP development. It assumes that users have access to a development environment with PHP and Composer installed. Additionally, it assumes that users have a basic understanding of cryptocurrencies and their functionalities.