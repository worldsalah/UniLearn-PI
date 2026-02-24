# Vertex AI Integration Setup Instructions

## Configuration Required

### 1. Google Cloud Project Setup
- Create a Google Cloud Project if you don't have one
- Enable the Vertex AI API in your project
- Enable the Cloud AI Platform API

### 2. Authentication Setup
- Create a service account in your Google Cloud project
- Grant the service account the "Vertex AI User" role
- Download the JSON key file
- Place the key file at: `config/vertex-ai-service-account.json`

### 3. Environment Variables
Update your `.env` file with your actual project ID:

```bash
VERTEX_AI_PROJECT_ID=your-actual-gcp-project-id
VERTEX_AI_LOCATION=us-central1
VERTEX_AI_MODEL=gemini-1.5-flash
VERTEX_AI_SERVICE_ACCOUNT_PATH=config/vertex-ai-service-account.json
```

### 4. Install Required Dependencies
Add these packages to your project:

```bash
composer require google/cloud-aiplatform
composer require google/auth
```

### 5. Service Configuration
The VertexApiService is already configured in `config/services.yaml`

## Features
- AI-powered question generation using Google's Gemini model
- Support for multiple categories and difficulty levels
- Both multiple choice and true/false questions
- Configurable question count (1-50 per request)

## Usage
1. Go to the quiz creation page
2. Click on "Generate Questions" tab
3. Select your preferences (category, difficulty, type, amount)
4. Click "Generate Questions with AI"
5. Review and import the generated questions

## Notes
- The service uses Gemini 1.5 Flash for fast, cost-effective generation
- Questions are generated based on educational best practices
- Each request includes proper validation and error handling
- The system maintains the existing translation features
