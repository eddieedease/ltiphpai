# LTI Tool Provider

This project implements an LTI Tool Provider (not a Platform/Consumer) supporting both LTI 1.1 and 1.3 using PHP Slim Framework. This means it can be integrated into Learning Management Systems (LMS) like Canvas, Moodle, or Blackboard.

## What is an LTI Tool Provider?
- A Tool Provider is an application that can be integrated into an LMS
- The LMS acts as the Platform/Consumer
- Students/Teachers access your tool through the LMS

## Security Components

### Nonces
Nonces (Numbers used ONCE) are crucial for LTI security:
- They prevent replay attacks
- Each LTI launch request contains a unique nonce
- The nonce can only be used once and expires quickly
- Our `lti_nonces` table tracks used nonces to prevent request replay attacks

## Setup

1. Install dependencies:
```bash
composer install
```

2. Configure your database:
- Create a new MySQL database
- Copy `config/config.example.php` to `config/config.php`
- Update database credentials in `config/config.php`

3. Import database schema:
```bash
mysql -u your_username -p your_database_name < database/schema.sql
```

4. Configure your MAMP Pro:
- Set document root to the `public` folder
- Enable URL rewriting

## LTI Configuration

### LTI 1.1
- Launch URL: `https://your-domain/lti/v1/launch`
- Consumer key: Configure in the database
- Shared secret: Configure in the database

### LTI 1.3
- Launch URL: `https://your-domain/lti/v3/launch`
- OIDC Login URL: `https://your-domain/lti/v3/login`
- Public key URL: `https://your-domain/lti/v3/keys`

## Result Handling

The tool provider stores results in two ways:
1. Locally in the database for record keeping and analytics
2. Sends results back to the LMS using:
   - LTI 1.1: Basic Outcomes Service
   - LTI 1.3: Assignment and Grade Services (AGS)

### Database Storage
- `lti_activities`: Stores information about each unique assignment
- `lti_results`: Stores student submissions and scores
  - Keeps track if results were successfully sent to LMS
  - Stores additional result data in JSON format
  - Allows for result history and analytics
