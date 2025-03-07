# WPschemaVUE - REST API Endpoints

Detta dokument beskriver alla REST API-endpoints som implementeras i WPschemaVUE-pluginet, deras funktionalitet, behörighetskrav och dataformat.

## Basadress

Alla endpoints finns under basadressen:
```
/wp-json/schedule/v1/
```

## Autentisering och säkerhet

### Cookie-autentisering
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

Exempel med axios:
```javascript
axios.get('/wp-json/schedule/v1/organizations', {
  withCredentials: true,
  headers: {
    'X-WP-Nonce': wpScheduleData.nonce
  }
})
```

### Behörighetskontroll
Varje endpoint implementerar strikt behörighetskontroll baserat på:
1. **Användarautentisering**: Kontrollerar att användaren är inloggad
2. **Rollbaserad behörighet**: Kontrollerar användarens roll i organisationen
3. **Hierarkisk behörighet**: Kontrollerar ärvda behörigheter från föräldraorganisationer
4. **Resursbehörighet**: Kontrollerar behörighet för specifika resurser
5. **Schemabehörighet**: Kontrollerar behörighet för specifika scheman

### Säkerhetsåtgärder
1. **CSRF-skydd**: Nonce-validering för alla modifierande anrop (POST, PUT, DELETE)
2. **Validering av indata**: Strikt validering av alla indata för att förhindra injektionsattacker
3. **Sanitering av utdata**: Säker hantering av data som returneras till klienten
4. **Begränsad information i felmeddelanden**: Felmeddelanden avslöjar inte känslig information

## Endpoints

### Organisationer

#### Hämta alla organisationer
```
GET /organizations
```

**Beskrivning:** Hämtar en lista över alla organisationer som användaren har tillgång till.

**Behörighet:** Inloggad användare  
**Svar:** Array med organisationsobjekt  
**Query-parametrar:**
- `parent_id` (valfri): Filtrera på föräldraorganisation
- `orderby` (valfri): Fält att sortera på (standard: 'name')
- `order` (valfri): Sorteringsordning ('ASC' eller 'DESC', standard: 'ASC')

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

**Implementationsdetaljer:**
- Använder materialiserad sökväg för effektiv hierarkisk filtrering
- Inkluderar antal barn för varje organisation för UI-rendering
- Filtrerar automatiskt bort organisationer användaren inte har tillgång till

#### Hämta en organisation
```
GET /organizations/{id}
```

**Beskrivning:** Hämtar detaljerad information om en specifik organisation.

**Behörighet:** Inloggad användare med tillgång till organisationen  
**Svar:** Ett organisationsobjekt eller 404

**Exempel på svar:**
```json
{
  "id": 1,
  "name": "Huvudkontoret",
  "parent_id": null,
  "path": "1",
  "created_at": "2023-01-01T12:00:00",
  "updated_at": "2023-01-01T12:00:00",
  "children_count": 3
}
```

#### Skapa organisation
```
POST /organizations
```

**Beskrivning:** Skapar en ny organisation, eventuellt som barn till en befintlig organisation.

**Behörighet:** Admin eller schemaadmin i föräldraorganisationen  
**Parametrar:**
```json
{
  "name": "Ny organisation",
  "parent_id": 1
}
```

**Validering:**
- `name`: Obligatoriskt, sträng, max 255 tecken
- `parent_id`: Valfritt, heltal, måste vara en giltig organisation

**Svar:** Det skapade organisationsobjektet eller 400 med felmeddelande

**Implementationsdetaljer:**
- Genererar automatiskt materialiserad sökväg baserat på föräldraorganisation
- Kontrollerar att användaren har behörighet i föräldraorganisationen
- Validerar att organisationsnamnet är unikt inom samma föräldraorganisation

#### Uppdatera organisation
```
PUT /organizations/{id}
```

**Beskrivning:** Uppdaterar en befintlig organisation.

**Behörighet:** Admin eller schemaadmin i organisationen  
**Parametrar:**
```json
{
  "name": "Uppdaterat namn",
  "parent_id": 2
}
```

**Validering:**
- `name`: Valfritt, sträng, max 255 tecken
- `parent_id`: Valfritt, heltal, måste vara en giltig organisation

**Svar:** Det uppdaterade organisationsobjektet eller 400/403 med felmeddelande

**Implementationsdetaljer:**
- Uppdaterar materialiserad sökväg om föräldraorganisationen ändras
- Uppdaterar rekursivt sökvägar för alla underorganisationer
- Kontrollerar cirkulära referenser (en organisation kan inte vara sin egen förälder)

#### Ta bort organisation
```
DELETE /organizations/{id}
```

**Beskrivning:** Tar bort en organisation om den inte har några barn.

**Behörighet:** Admin eller schemaadmin i föräldraorganisationen  
**Svar:** 200 vid lyckad borttagning eller 403/404 med felmeddelande

**Implementationsdetaljer:**
- Kontrollerar att organisationen inte har några barn innan borttagning
- Tar bort alla användarorganisationskopplingar för den borttagna organisationen
- Tar bort alla resurser som tillhör organisationen

---

### Användarorganisationer

#### Hämta alla användare i en organisation
```
GET /organizations/{id}/users
```

**Beskrivning:** Hämtar alla användare som tillhör en specifik organisation.

**Behörighet:** Inloggad användare med tillgång till organisationen  
**Svar:** Array med användarobjekt och deras roller

**Query-parametrar:**
- `role` (valfri): Filtrera på roll ('base', 'scheduler', 'admin')
- `orderby` (valfri): Fält att sortera på (standard: 'display_name')
- `order` (valfri): Sorteringsordning ('ASC' eller 'DESC', standard: 'ASC')

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

**Beskrivning:** Lägger till en användare till en organisation med en specifik roll.

**Behörighet:** Admin eller schemaadmin i organisationen  
**Parametrar:**
```json
{
  "user_id": 5,
  "role": "scheduler"
}
```

**Validering:**
- `user_id`: Obligatoriskt, heltal, måste vara en giltig WordPress-användare
- `role`: Obligatoriskt, sträng, måste vara 'base', 'scheduler' eller 'admin'

**Svar:** Det skapade användarorganisationsobjektet eller 400/403 med felmeddelande

**Implementationsdetaljer:**
- Kontrollerar att användaren inte redan finns i organisationen
- Validerar att användar-ID:t motsvarar en giltig WordPress-användare
- Säkerställer att rollen är giltig

#### Uppdatera användarroll
```
PUT /organizations/{id}/users/{user_id}
```

**Beskrivning:** Uppdaterar en användares roll i en organisation.

**Behörighet:** Admin eller schemaadmin i organisationen  
**Parametrar:**
```json
{
  "role": "admin"
}
```

**Validering:**
- `role`: Obligatoriskt, sträng, måste vara 'base', 'scheduler' eller 'admin'

**Svar:** Det uppdaterade användarorganisationsobjektet eller 400/403 med felmeddelande

**Implementationsdetaljer:**
- Kontrollerar att användaren finns i organisationen
- Validerar att den nya rollen är giltig
- Rensar behörighetscachen för användaren

#### Ta bort användare från organisation
```
DELETE /organizations/{id}/users/{user_id}
```

**Beskrivning:** Tar bort en användare från en organisation.

**Behörighet:** Admin eller schemaadmin i organisationen  
**Svar:** 200 vid lyckad borttagning eller 403/404 med felmeddelande

**Implementationsdetaljer:**
- Kontrollerar att användaren finns i organisationen
- Tar inte bort scheman som tillhör användaren
- Rensar behörighetscachen för användaren

---

### Resurser

#### Hämta alla resurser för en organisation
```
GET /organizations/{id}/resources
```

**Beskrivning:** Hämtar alla resurser som tillhör en specifik organisation.

**Behörighet:** Inloggad användare med tillgång till organisationen  
**Svar:** Array med resursobjekt

**Query-parametrar:**
- `orderby` (valfri): Fält att sortera på (standard: 'name')
- `order` (valfri): Sorteringsordning ('ASC' eller 'DESC', standard: 'ASC')

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

**Beskrivning:** Skapar en ny resurs i en organisation.

**Behörighet:** Admin eller schemaadmin i organisationen  
**Parametrar:**
```json
{
  "name": "Ny resurs",
  "description": "Beskrivning av resursen",
  "color": "#33FF57"
}
```

**Validering:**
- `name`: Obligatoriskt, sträng, max 255 tecken
- `description`: Valfritt, sträng
- `color`: Valfritt, sträng, måste vara en giltig HEX-färgkod (#RRGGBB)

**Svar:** Det skapade resursobjektet eller 400/403 med felmeddelande

**Implementationsdetaljer:**
- Validerar att färgkoden har korrekt format
- Tilldelar en standardfärg om ingen anges
- Kopplar resursen till den angivna organisationen

#### Uppdatera resurs
```
PUT /resources/{id}
```

**Beskrivning:** Uppdaterar en befintlig resurs.

**Behörighet:** Admin eller schemaadmin i organisationen resursen tillhör  
**Parametrar:**
```json
{
  "name": "Uppdaterat namn",
  "description": "Ny beskrivning",
  "color": "#3357FF"
}
```

**Validering:**
- `name`: Valfritt, sträng, max 255 tecken
- `description`: Valfritt, sträng
- `color`: Valfritt, sträng, måste vara en giltig HEX-färgkod (#RRGGBB)

**Svar:** Det uppdaterade resursobjektet eller 400/403 med felmeddelande

**Implementationsdetaljer:**
- Validerar att färgkoden har korrekt format
- Uppdaterar endast de fält som anges i anropet
- Kontrollerar att användaren har behörighet i organisationen resursen tillhör

#### Ta bort resurs
```
DELETE /resources/{id}
```

**Beskrivning:** Tar bort en resurs om den inte har några scheman.

**Behörighet:** Admin eller schemaadmin i organisationen resursen tillhör  
**Svar:** 200 vid lyckad borttagning eller 403/404 med felmeddelande

**Implementationsdetaljer:**
- Kontrollerar att resursen inte har några scheman innan borttagning
- Kontrollerar att användaren har behörighet i organisationen resursen tillhör

---

### Scheman

#### Hämta scheman för en resurs
```
GET /schedules/resource/{resource_id}
```

**Beskrivning:** Hämtar alla scheman för en specifik resurs inom ett tidsintervall.

**Behörighet:** Inloggad användare med tillgång till organisationen resursen tillhör  
**Query-parametrar:**
- `start_date`: Startdatum (YYYY-MM-DD)
- `end_date`: Slutdatum (YYYY-MM-DD)
- `status` (valfri): Filtrera på status ('scheduled', 'confirmed', 'completed')

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

**Implementationsdetaljer:**
- Validerar att start- och slutdatum har korrekt format
- Begränsar tidsintervallet till max 31 dagar för prestandaskäl
- Inkluderar användarinformation för varje schemapost

#### Hämta mitt schema
```
GET /schedules/my-schedule
```

**Beskrivning:** Hämtar alla scheman för den inloggade användaren inom ett tidsintervall.

**Behörighet:** Inloggad användare  
**Query-parametrar:**
- `start_date`: Startdatum (YYYY-MM-DD)
- `end_date`: Slutdatum (YYYY-MM-DD)
- `status` (valfri): Filtrera på status ('scheduled', 'confirmed', 'completed')

**Svar:** Array med schemaposter för inloggad användare

**Implementationsdetaljer:**
- Validerar att start- och slutdatum har korrekt format
- Begränsar tidsintervallet till max 31 dagar för prestandaskäl
- Inkluderar resursinformation för varje schemapost

#### Skapa schemapost
```
POST /schedules
```

**Beskrivning:** Skapar en ny schemapost för en användare och resurs.

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

**Validering:**
- `user_id`: Obligatoriskt, heltal, måste vara en giltig WordPress-användare
- `resource_id`: Obligatoriskt, heltal, måste vara en giltig resurs
- `start_time`: Obligatoriskt, datetime, måste vara före end_time
- `end_time`: Obligatoriskt, datetime, måste vara efter start_time
- `notes`: Valfritt, sträng
- `status`: Valfritt, sträng, måste vara 'scheduled', 'confirmed' eller 'completed'

**Svar:** Det skapade schemaobjektet eller 400/403 med felmeddelande

**Implementationsdetaljer:**
- Kontrollerar att användaren har behörighet att skapa schema för den angivna användaren
- Validerar att start- och sluttider har korrekt format och ordning
- Kontrollerar överlappande scheman för användaren
- Spårar vem som skapade schemat

#### Uppdatera schemapost
```
PUT /schedules/{id}
```

**Beskrivning:** Uppdaterar en befintlig schemapost.

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

**Validering:**
- `start_time`: Valfritt, datetime, måste vara före end_time
- `end_time`: Valfritt, datetime, måste vara efter start_time
- `notes`: Valfritt, sträng
- `status`: Valfritt, sträng, måste vara 'scheduled', 'confirmed' eller 'completed'

**Svar:** Det uppdaterade schemaobjektet eller 400/403 med felmeddelande

**Implementationsdetaljer:**
- Kontrollerar att användaren har behörighet att uppdatera schemat
- Validerar att start- och sluttider har korrekt format och ordning
- Kontrollerar överlappande scheman för användaren om tiderna ändras
- Uppdaterar endast de fält som anges i anropet

#### Ta bort schemapost
```
DELETE /schedules/{id}
```

**Beskrivning:** Tar bort en schemapost.

**Behörighet:** 
- Admin eller schemaadmin i organisationen
- Basanvändare kan bara ta bort sina egna scheman

**Svar:** 200 vid lyckad borttagning eller 403/404 med felmeddelande

**Implementationsdetaljer:**
- Kontrollerar att användaren har behörighet att ta bort schemat
- Tar bort schemat permanent från databasen

---

### Användarinformation

#### Hämta inloggad användares information
```
GET /me
```

**Beskrivning:** Hämtar information om den inloggade användaren och dennes organisationer.

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

**Implementationsdetaljer:**
- Hämtar grundläggande användarinformation från WordPress
- Hämtar alla organisationer användaren tillhör med roller
- Inkluderar inte känslig information som lösenord eller säkerhetsinställningar

## Felhantering

Alla API-svar följer en konsekvent struktur för att underlätta felhantering i klientapplikationer.

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
- `resource_in_use`: Resursen kan inte tas bort eftersom den används
- `validation_error`: Valideringsfel i indata
- `conflict`: Konflikt med befintliga data (t.ex. överlappande scheman)
- `server_error`: Internt serverfel

## Säkerhetsprinciper

1. **Behörighetskontroll**: Alla endpoints kontrollerar användarens behörighet
   - Använder hierarkisk behörighetsmodell för organisationer
   - Implementerar rollbaserad åtkomstkontroll
   - Cachning av behörighetskontroller för prestanda

2. **Validering av indata**: Alla indata valideras noga
   - Typkontroll för alla parametrar
   - Formatvalidering för datum, tider och färgkoder
   - Begränsningar på stränglängder och värdeintervall

3. **CSRF-skydd**: Nonce används för att förhindra CSRF-attacker
   - Validering av nonce för alla modifierande anrop
   - Automatisk generering av nonce i WordPress

4. **Felsäker information**: Felmeddelanden röjer inte känslig information
   - Generiska felmeddelanden för autentiserings- och behörighetsfel
   - Detaljerade felmeddelanden för valideringsfel
   - Loggning av detaljerade fel på serversidan

## Prestandaoptimering

1. **Cachning**: Implementerar cachning för tunga operationer
   - Behörighetskontroller cachas för att minska databasanrop
   - Organisationshierarkin cachas för effektiv traversering

2. **Effektiv databasanvändning**: Optimerade databasfrågor
   - Använder materialiserad sökväg för hierarkiska frågor
   - Begränsar mängden data som hämtas med filtrering och paginering

3. **Validering på klientsidan**: Implementerar validering både på klient- och serversidan
   - Minskar onödiga serveranrop för felaktiga data
   - Ger snabb feedback till användaren

## Användning i Vue-applikationen

API-endpointerna används i Vue-applikationen genom en dedikerad API-klass som hanterar alla anrop och felhantering. Exempel på användning:

```javascript
// Exempel på användning i Vue-komponenten
import { useOrganizationsStore } from '@/stores/organizations';

export default {
  setup() {
    const organizationsStore = useOrganizationsStore();
    
    // Hämta alla organisationer
    organizationsStore.fetchOrganizations();
    
    // Skapa en ny organisation
    const createOrganization = async (name, parentId) => {
      try {
        await organizationsStore.createOrganization({ name, parent_id: parentId });
        // Visa bekräftelsemeddelande
      } catch (error) {
        // Visa felmeddelande
      }
    };
    
    return {
      organizations: organizationsStore.organizations,
      createOrganization
    };
  }
};
