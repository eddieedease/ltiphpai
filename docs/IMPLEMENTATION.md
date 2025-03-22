# LTI Implementation Guide

## Generate Key Pairs (LTI 1.3)

1. Generate RSA key pair:
```bash
# Generate private key
openssl genpkey -algorithm RSA -out private.key -pkeyopt rsa_keygen_bits:2048

# Generate public key
openssl rsa -pubout -in private.key -out public.key
```

2. Place keys in `config/keys/` directory
3. Update config.php with key paths

## Setting Up a New Client

### LTI 1.1 Client

1. Insert client credentials:
```sql
INSERT INTO lti_consumers (consumer_key, secret)
VALUES ('your_key', 'your_secret');
```

2. Configure LMS (example for Moodle):
   - Tool URL: https://your-domain/lti/v1/launch
   - Consumer Key: your_key
   - Shared Secret: your_secret

### LTI 1.3 Client

1. Insert platform configuration:
```sql
INSERT INTO lti13_deployments (
    issuer,
    client_id,
    deployment_id,
    platform_key_set_url,
    access_token_url,
    auth_token_url
) VALUES (
    'https://lms.example.com',
    'client123',
    'deploy123',
    'https://lms.example.com/keyset',
    'https://lms.example.com/token',
    'https://lms.example.com/auth'
);
```

2. Configure LMS:
   - Login URL: https://your-domain/lti/v3/login
   - Launch URL: https://your-domain/lti/v3/launch
   - Public JWK URL: https://your-domain/lti/v3/keys
   - Client ID: client123
   - Deployment ID: deploy123

## Sample Implementation Flow

### 1. Frontend (Angular) Implementation

```typescript
// lti.service.ts
@Injectable()
export class LtiService {
  constructor(private http: HttpClient) {}

  // Handle LTI launch response
  handleLaunch(launchData: any) {
    return this.http.post('/api/lti/v1/launch', launchData)
      .pipe(
        tap(response => {
          // Store JWT token
          localStorage.setItem('lti_token', response.token);
        })
      );
  }

  // Submit grade
  submitGrade(score: number, extraData?: any) {
    return this.http.post('/api/lti/grades', {
      score,
      extra_data: extraData
    }, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('lti_token')}`
      }
    });
  }
}
```

### 2. Example Launch Flow

1. LMS initiates launch
2. Your tool receives launch request
3. Tool validates launch and returns JWT
4. Frontend stores JWT
5. Use JWT for subsequent API calls

### 3. Grade Submission Example

```typescript
// component.ts
async submitAssignment() {
  const result = await this.ltiService.submitGrade(0.85, {
    completed_at: new Date(),
    time_spent: 300
  }).toPromise();

  console.log('Grade submitted:', result);
}
```

## Testing

1. Use our test LMS at https://your-domain/test-lms
2. Sample credentials:
   - Teacher: teacher@example.com / password
   - Student: student@example.com / password

3. Test launch URL:
```
https://your-domain/test-launch?
  key=sample_key&
  course=TEST101&
  user=student1
```
