# WPschemaVUE - REST API Endpoints

Detta dokument beskriver alla REST API-endpoints som implementeras i WPschemaVUE-pluginet.

## Basadress

Alla endpoints finns under basadressen:
```
/wp-json/schedule/v1/
```

## Autentisering

Alla API-anrop kräver WordPress cookie-autentisering. Detta säkerställs genom att:

1. Lägga till `X-WP-Nonce`-header med ett giltigt nonce
2. Inkludera `credentials: 'same-origin'` i fetch/axios-anrop

Exempel med fetch:
```javascript
fetch('/wp-json/schedule/v1/organizations', {
  method: 'GET',
  credentials: 'same-origin',
  headers: {
    'X-WP-Nonce': wpScheduleData.nonce
  }
})
```

## Endpoints

### Organisationer

#### Hämta alla organisationer
```
GET /organizations
```

**Behörighet:** Inloggad användare  
**Svar:** Array med organisationsobjekt  
**Query-parametrar:**
- `parent_id` (valfri): Filtrera på föräldraorganisation

**Exempel på svar:**
```json
[
  {
    "id": 1,
    "name": "Huvudkontoret",
    "parent_id": null,
    "path": "1",
    "created_at": "2023-01-01T12:00:00",
    "updated_at": "2023-01-01T12:00:00",
    "children_count": 3
  },
  {
    "id": 2,
    "name": "IT-avdelningen",
    "parent_id": 1,
    "path": "1/2",
    "created_at": "2023-01-01T12:00:00",
    "updated_at": "2023-01-01T12:00:00",
    "children_count": 0
  }
]
```

#### Hämta en organisation
```
GET /organizations/{id}
```

**Behörighet:** Inloggad användare med tillgång till organisationen  
**Svar:** Ett organisationsobjekt eller 404

#### Skapa organisation
```
POST /organizations
```

**Behörighet:** Admin eller schemaadmin i föräldraorganisationen  
**Parametrar:**
```json
{
  "name": "Ny organisation",
  "parent_id": 1
}
```

**Svar:** Det skapade organisationsobjektet eller 400 med felmeddelande

#### Uppdatera organisation
```
PUT /organizations/{id}
```

**Behörighet:** Admin eller schemaadmin i organisationen  
**Parametrar:**
```json
{
  "name": "Uppdaterat namn"
}
```

**Svar:** Det uppdaterade organisationsobjektet eller 400/403 med felmeddelande

#### Ta bort organisation
```
DELETE /organizations/{id}
```

**Behörighet:** Admin eller schemaadmin i föräldraorganisationen  
**Svar:** 200 vid lyckad borttagning eller 403/404 med felmeddelande

---

### Användarorganisationer

#### Hämta alla användare i en organisation
```
GET /organizations/{id}/users
```

**Behörighet:** Inloggad användare med tillgång till organisationen  
**Svar:** Array med användarobjekt och deras roller

**Exempel på svar:**
```json
[
  {
    "id": 1,
    "user_id": 5,
    "organization_id": 1,
    "role": "admin",
    "created_at": "2023-01-01T12:00:00",
    "updated_at": "2023-01-01T12:00:00",
    "user_data": {
      "display_name": "Johan Svensson",
      "user_email": "johan@example.com"
    }
  }
]
```

#### Lägg till användare till organisation
```
POST /organizations/{id}/users
```

**Behörighet:** Admin eller schemaadmin i organisationen  
**Parametrar:**
```json
{
  "user_id": 5,
  "role": "scheduler"
}
```

**Svar:** Det skapade användarorganisationsobjektet eller 400/403 med felmeddelande

#### Uppdatera användarroll
```
PUT /organizations/{id}/users/{user_id}
```

**Behörighet:** Admin eller schemaadmin i organisationen  
**Parametrar:**
```json
{
  "role": "admin"
}
```

**Svar:** Det uppdaterade användarorganisationsobjektet eller 400/403 med felmeddelande

#### Ta bort användare från organisation
```
DELETE /organizations/{id}/users/{user_id}
```

**Behörighet:** Admin eller schemaadmin i organisationen  
**Svar:** 200 vid lyckad borttagning eller 403/404 med felmeddelande

---

### Resurser

#### Hämta alla resurser för en organisation
```
GET /organizations/{id}/resources
```

**Behörighet:** Inloggad användare med tillgång till organisationen  
**Svar:** Array med resursobjekt

**Exempel på svar:**
```json
[
  {
    "id": 1,
    "name": "Konferensrum A",
    "description": "Stort konferensrum med projektor",
    "organization_id": 1,
    "color": "#FF5733",
    "created_at": "2023-01-01T12:00:00",
    "updated_at": "2023-01-01T12:00:00"
  }
]
```

#### Skapa resurs
```
POST /organizations/{id}/resources
```

**Behörighet:** Admin eller schemaadmin i organisationen  
**Parametrar:**
```json
{
  "name": "Ny resurs",
  "description": "Beskrivning av resursen",
  "color": "#33FF57"
}
```

**Svar:** Det skapade resursobjektet eller 400/403 med felmeddelande

#### Uppdatera resurs
```
PUT /resources/{id}
```

**Behörighet:** Admin eller schemaadmin i organisationen resursen tillhör  
**Parametrar:**
```json
{
  "name": "Uppdaterat namn",
  "description": "Ny beskrivning",
  "color": "#3357FF"
}
```

**Svar:** Det uppdaterade resursobjektet eller 400/403 med felmeddelande

#### Ta bort resurs
```
DELETE /resources/{id}
```

**Behörighet:** Admin eller schemaadmin i organisationen resursen tillhör  
**Svar:** 200 vid lyckad borttagning eller 403/404 med felmeddelande

---

### Scheman

#### Hämta scheman för en resurs
```
GET /schedules/resource/{resource_id}
```

**Behörighet:** Inloggad användare med tillgång till organisationen resursen tillhör  
**Query-parametrar:**
- `start_date`: Startdatum (YYYY-MM-DD)
- `end_date`: Slutdatum (YYYY-MM-DD)

**Svar:** Array med schemaposter

**Exempel på svar:**
```json
[
  {
    "id": 1,
    "user_id": 5,
    "resource_id": 1,
    "start_time": "2023-01-01T08:00:00",
    "end_time": "2023-01-01T16:00:00",
    "notes": "Ordinarie arbetspass",
    "status": "confirmed",
    "created_by": 1,
    "created_at": "2022-12-15T12:00:00",
    "updated_at": "2022-12-15T12:00:00",
    "user_data": {
      "display_name": "Johan Svensson"
    }
  }
]
```

#### Hämta mitt schema
```
GET /schedules/my-schedule
```

**Behörighet:** Inloggad användare  
**Query-parametrar:**
- `start_date`: Startdatum (YYYY-MM-DD)
- `end_date`: Slutdatum (YYYY-MM-DD)

**Svar:** Array med schemaposter för inloggad användare

#### Skapa schemapost
```
POST /schedules
```

**Behörighet:** 
- Admin eller schemaadmin i organisationen
- Basanvändare kan bara skapa sina egna scheman

**Parametrar:**
```json
{
  "user_id": 5,
  "resource_id": 1,
  "start_time": "2023-01-01T08:00:00",
  "end_time": "2023-01-01T16:00:00",
  "notes": "Ordinarie arbetspass",
  "status": "scheduled"
}
```

**Svar:** Det skapade schemaobjektet eller 400/403 med felmeddelande

#### Uppdatera schemapost
```
PUT /schedules/{id}
```

**Behörighet:** 
- Admin eller schemaadmin i organisationen
- Basanvändare kan bara uppdatera sina egna scheman

**Parametrar:** (alla valfria)
```json
{
  "start_time": "2023-01-01T09:00:00",
  "end_time": "2023-01-01T17:00:00",
  "notes": "Uppdaterat arbetspass",
  "status": "confirmed"
}
```

**Svar:** Det uppdaterade schemaobjektet eller 400/403 med felmeddelande

#### Ta bort schemapost
```
DELETE /schedules/{id}
```

**Behörighet:** 
- Admin eller schemaadmin i organisationen
- Basanvändare kan bara ta bort sina egna scheman

**Svar:** 200 vid lyckad borttagning eller 403/404 med felmeddelande

---

### Användarinformation

#### Hämta inloggad användares information
```
GET /me
```

**Behörighet:** Inloggad användare  
**Svar:** Användarinformation och organisationer användaren tillhör

**Exempel på svar:**
```json
{
  "id": 5,
  "username": "johan",
  "display_name": "Johan Svensson",
  "email": "johan@example.com",
  "organizations": [
    {
      "id": 1,
      "name": "Huvudkontoret",
      "role": "admin"
    },
    {
      "id": 2,
      "name": "IT-avdelningen",
      "role": "scheduler"
    }
  ]
}
```

## Felhantering

Alla API-svar följer en konsekvent struktur:

### Lyckat svar
```json
{
  "success": true,
  "data": { /* Svarsdata */ }
}
```

### Felsvar
```json
{
  "success": false,
  "error": {
    "code": "invalid_input",
    "message": "Ett användarvänligt felmeddelande",
    "details": { /* Detaljerad felinformation */ }
  }
}
```

### Felkoder
- `not_authenticated`: Användaren är inte inloggad
- `not_authorized`: Användaren saknar behörighet
- `invalid_input`: Felaktiga indata
- `not_found`: Resursen hittades inte
- `server_error`: Internt serverfel

## Säkerhetsprinciper

1. **Behörighetskontroll**: Alla endpoints kontrollerar användarens behörighet
2. **Validering av indata**: Alla indata valideras noga
3. **CSRF-skydd**: Nonce används för att förhindra CSRF-attacker
4. **Felsäker information**: Felmeddelanden röjer inte känslig information
