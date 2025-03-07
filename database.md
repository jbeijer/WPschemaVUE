# WPschemaVUE - Databasstruktur

## Översikt

WPschemaVUE använder en optimerad databasdesign för att hantera hierarkiska organisationer, användarbehörigheter, resurser och schemaläggning. Designen fokuserar på prestanda, skalbarhet och dataintegritet.

## Tabeller

Pluginet skapar följande tabeller i WordPress-databasen:

### wp_schedule_organizations
Lagrar information om organisationer i hierarkisk struktur.

| Kolumn          | Typ                | Beskrivning                                           |
|-----------------|--------------------|---------------------------------------------------------|
| id              | bigint(20) unsigned| Primärnyckel                                          |
| name            | varchar(255)       | Organisationens namn                                  |
| parent_id       | bigint(20) unsigned| ID för föräldraorganisation (NULL om det är huvudorganisation) |
| path            | varchar(255)       | Materialiserad sökväg för effektiv hierarkisk sökning  |
| created_at      | datetime           | Tidsstämpel för skapande                              |
| updated_at      | datetime           | Tidsstämpel för senaste uppdatering                   |

**Indexering**:
- PRIMARY KEY på `id`
- INDEX på `parent_id` för snabb sökning efter barn
- INDEX på `path` för effektiv hierarkisk traversering

**Fördelar med materialiserad sökväg**:
- Möjliggör snabb sökning i hierarkin utan rekursiva frågor
- Path kan lagras i format som "1/4/7" för att representera en sökväg i hierarkin
- Underlättar sökning efter alla underorganisationer (LIKE "1/4/%")
- Eliminerar behovet av rekursiva databasfrågor som kan vara prestandakrävande
- Möjliggör effektiv filtrering och sortering baserat på hierarkisk position

**Implementationsdetaljer**:
- Path uppdateras automatiskt när en organisation skapas eller flyttas i hierarkin
- När en föräldraorganisation flyttas, uppdateras path för alla dess barn rekursivt
- Path används för att effektivt implementera ärvda behörigheter i hierarkin

### wp_schedule_user_organizations
Kopplar användare till organisationer med roller.

| Kolumn          | Typ                | Beskrivning                                           |
|-----------------|--------------------|---------------------------------------------------------|
| id              | bigint(20) unsigned| Primärnyckel                                          |
| user_id         | bigint(20) unsigned| WordPress användar-ID                                 |
| organization_id | bigint(20) unsigned| ID för organisationen                                |
| role            | enum               | Roll: 'base', 'scheduler', 'admin'                     |
| created_at      | datetime           | Tidsstämpel för skapande                              |
| updated_at      | datetime           | Tidsstämpel för senaste uppdatering                   |

**Indexering**:
- PRIMARY KEY på `id`
- UNIQUE KEY på `user_id, organization_id` (en användare kan endast ha en roll per organisation)
- INDEX på `organization_id` för snabb sökning efter användare i en organisation
- INDEX på `user_id` för snabb sökning efter organisationer för en användare

**Rollhierarki**:
- **base**: Grundläggande behörighet att se scheman och redigera egna arbetspass
- **scheduler**: Utökad behörighet att hantera scheman för alla i organisationen
- **admin**: Fullständig behörighet att hantera organisationen, resurser och användare

**Implementationsdetaljer**:
- Rollerna är implementerade som en enum för att säkerställa dataintegritet
- Behörigheter ärvs nedåt i organisationshierarkin genom att kontrollera föräldraorganisationer
- Cachning används för att optimera behörighetskontroller

### wp_schedule_resources
Resurser (t.ex. scheman/avdelningar) inom organisationer.

| Kolumn          | Typ                | Beskrivning                                           |
|-----------------|--------------------|---------------------------------------------------------|
| id              | bigint(20) unsigned| Primärnyckel                                          |
| name            | varchar(255)       | Resursens namn                                        |
| description     | text               | Beskrivning av resursen                               |
| organization_id | bigint(20) unsigned| ID för organisationen resursen tillhör                |
| color           | varchar(7)         | HEX-färgkod för visualisering av resursen i scheman    |
| created_at      | datetime           | Tidsstämpel för skapande                              |
| updated_at      | datetime           | Tidsstämpel för senaste uppdatering                   |

**Indexering**:
- PRIMARY KEY på `id`
- INDEX på `organization_id` för snabb sökning efter resurser i en organisation

**Användningsområden**:
- Resurser kan representera fysiska platser (rum, avdelningar)
- Resurser kan representera logiska enheter (projekt, team)
- Resurser kan representera utrustning eller andra schemaläggningsobjekt
- Färgkodning används för att visuellt särskilja resurser i schemakalendern

**Implementationsdetaljer**:
- Resurser är alltid kopplade till en specifik organisation
- Behörighetskontroll för resurser baseras på organisationstillhörighet
- Färgkoder valideras för att säkerställa korrekt HEX-format (#RRGGBB)

### wp_schedule_entries
Schemaläggning (arbetspass).

| Kolumn          | Typ                | Beskrivning                                           |
|-----------------|--------------------|---------------------------------------------------------|
| id              | bigint(20) unsigned| Primärnyckel                                          |
| user_id         | bigint(20) unsigned| WordPress användar-ID för den schemalagda användaren  |
| resource_id     | bigint(20) unsigned| ID för resursen                                       |
| start_time      | datetime           | Starttid för arbetspasset                             |
| end_time        | datetime           | Sluttid för arbetspasset                              |
| notes           | text               | Anteckningar för arbetspasset                         |
| status          | enum               | Status: 'scheduled', 'confirmed', 'completed'          |
| created_by      | bigint(20) unsigned| ID för användaren som skapade posten                  |
| created_at      | datetime           | Tidsstämpel för skapande                              |
| updated_at      | datetime           | Tidsstämpel för senaste uppdatering                   |

**Indexering**:
- PRIMARY KEY på `id`
- INDEX på `user_id` för snabb sökning efter scheman för en användare
- INDEX på `resource_id` för snabb sökning efter scheman för en resurs
- INDEX på `start_time, end_time` för snabb sökning på tidsintervall

**Statushantering**:
- **scheduled**: Arbetspasset är planerat men inte bekräftat
- **confirmed**: Arbetspasset är bekräftat av användaren eller administratören
- **completed**: Arbetspasset är genomfört

**Implementationsdetaljer**:
- Automatisk kontroll för överlappande scheman för samma användare
- Validering av start- och sluttider för att säkerställa korrekt ordning
- Spårning av vem som skapade schemat för revisionsändamål

## Relationer mellan tabeller

```
wp_schedule_organizations
  ↑ (parent_id)
  ↓ (id)
wp_schedule_organizations

wp_schedule_organizations
  ↑ (id)
  ↓ (organization_id)
wp_schedule_user_organizations

wp_schedule_organizations
  ↑ (id)
  ↓ (organization_id)
wp_schedule_resources

wp_schedule_resources
  ↑ (id)
  ↓ (resource_id)
wp_schedule_entries

wp_users (WordPress core)
  ↑ (ID)
  ↓ (user_id)
wp_schedule_user_organizations

wp_users (WordPress core)
  ↑ (ID)
  ↓ (user_id)
wp_schedule_entries
```

## Fördelar med denna databasdesign

1. **Prestanda**:
   - Optimerad indexering för snabba sökningar
   - Materialiserad sökvägsmönster för effektiv hierarkisk traversering
   - Tidsstämplar för alla tabeller underlättar felsökning och loggning
   - Noga utvalda index för att balansera sökhastighet och databasstorlek

2. **Skalbarhet**:
   - Designen stödjer komplexa hierarkiska strukturer med obegränsat djup
   - Effektiv för både små organisationer och stora företag med tusentals användare
   - Materialiserad sökväg möjliggör snabb traversering även i djupa hierarkier

3. **Integritet**:
   - Främmande nycklar kan implementeras för att upprätthålla databasintegritet
   - Unika index förhindrar dubbletter och säkerställer datakonsistens
   - Enum-typer för roller och status säkerställer giltiga värden

4. **Flexibilitet**:
   - Designen stödjer olika typer av organisationsstrukturer
   - Resurser kan anpassas för olika användningsområden
   - Schemaläggning kan användas för olika typer av tidsbokning

## Databasmigrering och underhåll

### Installation
Vid plugin-aktivering körs en migreringsrutin som:
1. Kontrollerar om tabellerna redan finns
2. Skapar tabeller som saknas med korrekt struktur och index
3. Säkerställer att alla nödvändiga kolumner finns

### Uppdatering
Vid plugin-uppdatering:
1. Kontrollerar den lagrade databasversionen mot den aktuella versionen
2. Kör nödvändiga migreringar för att uppdatera databasstrukturen
3. Uppdaterar databasversionen i WordPress-inställningar

### Avinstallation
Vid avinstallation av pluginet:
1. Användaren kan välja att behålla eller ta bort all data
2. Om borttagning väljs, tas alla tabeller och inställningar bort
3. Transients och andra temporära data rensas alltid

## Prestandaoptimering

1. **Indexering**:
   - Noga utvalda index för att optimera vanliga sökningar
   - Balans mellan sökhastighet och databasstorlek

2. **Materialiserad sökväg**:
   - Eliminerar behovet av rekursiva frågor för hierarkisk traversering
   - Möjliggör effektiv filtrering på hierarkisk position

3. **Cachning**:
   - Behörighetskontroller cachas för att minska databasbelastning
   - WordPress transients används för att cacha tunga frågor

4. **Effektiv datamodellering**:
   - Normaliserad databasdesign för att minimera redundans
   - Denormalisering där det är motiverat för prestanda (t.ex. materialiserad sökväg)
