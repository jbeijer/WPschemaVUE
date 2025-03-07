# WPschemaVUE - Databasstruktur

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
- INDEX på `organization_id`
- INDEX på `user_id`

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
- INDEX på `organization_id`

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
- INDEX på `user_id`
- INDEX på `resource_id`
- INDEX på `start_time, end_time` för snabb sökning på tidsintervall

## Fördelar med denna databasdesign

1. **Prestanda**:
   - Optimerad indexering för snabba sökningar
   - Materialiserad sökvägsmönster för effektiv hierarkisk traversering
   - Tidsstämplar för alla tabeller underlättar felsökning och loggning

2. **Skalbarhet**:
   - Designen stödjer komplexa hierarkiska strukturer
   - Effektiv för både små och stora organisationer

3. **Integritet**:
   - Främmande nycklar kan implementeras för att upprätthålla databasintegritet
   - Unika index förhindrar dubbletter

## Databasmigrering

Vid plugin-aktivering körs en migreringsrutin som:
1. Kontrollerar om tabellerna redan finns
2. Skapar tabeller som saknas
3. Uppdaterar befintliga tabeller vid behov (för framtida versioner)

Databasen kan även rensas helt vid avinstallation av pluginet om användaren väljer det.
