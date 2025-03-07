# WPschemaVUE - Schema Management Plugin

## Översikt
WPschemaVUE är ett WordPress-plugin för hantering av scheman inom organisationer med hierarkisk struktur. Pluginet använder Vue 3 för frontend-gränssnittet och erbjuder både en administratörsdel och en publik del. Systemet är särskilt utformat för att hantera komplexa organisationsstrukturer med olika behörighetsnivåer.

## Funktionalitet

### Organisationshantering
- **Hierarkisk struktur**: Organisationer kan ha obegränsat antal nivåer av underorganisationer
- **Materialiserad sökväg**: Effektiv hantering av hierarkin med optimerad databasdesign
- **Användarkoppling**: Användare kan tillhöra flera organisationer med olika roller
- **Ärvda behörigheter**: Behörigheter ärvs automatiskt nedåt i hierarkin för smidig administration

### Behörighetshantering
Pluginet använder tre behörighetsnivåer med tydlig ansvarsfördelning:
- **Bas**: Kan se alla scheman i sin organisation och redigera sina egna arbetspass
- **Schemaläggare**: Kan se och redigera allas schema inom sin organisation
- **Schemaadmin**: Har alla behörigheter inklusive att skapa resurser/nya scheman och hantera organisationsstrukturen

### Resurshantering
- **Flexibla resurser**: Resurser kan representera rum, avdelningar, projekt eller andra schemaläggningsobjekt
- **Visuell anpassning**: Varje resurs kan tilldelas en unik färg för tydlig visualisering i schemakalendern
- **Organisationstillhörighet**: Resurser tillhör specifika organisationer med tillhörande behörighetskontroll

### Schemaläggning
- **Avancerad kalendervy**: Intuitiv visualisering av scheman med färgkodning
- **Konflikthantering**: Automatisk kontroll för att förhindra överlappande scheman
- **Statushantering**: Scheman kan ha olika status (planerad, bekräftad, genomförd)
- **Anteckningar**: Möjlighet att lägga till information till varje schemapost

### Teknisk uppbyggnad
- **Frontend**: Vue 3 med Composition API och Pinia för state management
- **Backend**: WordPress PHP-klasser med objektorienterad design
- **API**: RESTful API med omfattande dokumentation och behörighetskontroll
- **Databas**: Optimerad databasdesign med materialiserad sökväg för hierarkisk traversering
- **Säkerhet**: WordPress cookie-autentisering och CSRF-skydd för alla API-anrop
- **Prestanda**: Implementerad cachning för behörighetskontroller och tunga databasfrågor

## Fördelar
1. **Flexibilitet**: Anpassningsbar för olika typer av organisationer och schemaläggningsbehov
2. **Skalbarhet**: Hierarkisk struktur möjliggör hantering av både små och stora organisationer
3. **Användarvänlighet**: Modernt gränssnitt med Vue 3 gör det enkelt för användare att hantera scheman
4. **Säkerhet**: Robust behörighetshantering säkerställer att användare endast kan se och redigera data de har behörighet till
5. **Prestanda**: Optimerad databasdesign och cachning ger snabb respons även för stora organisationer
6. **Integration**: Sömlös integration med WordPress för enkel installation och användning

## Användningsområden
- **Företag**: Hantera scheman för olika avdelningar och team inom en organisation
- **Sjukvård**: Schemaläggning för läkare, sjuksköterskor och annan vårdpersonal på olika avdelningar
- **Utbildning**: Hantera scheman för lärare, klassrum och kurser
- **Restauranger**: Schemaläggning för personal med olika roller och arbetspass
- **Evenemang**: Hantera resurser och personal för olika evenemang och platser
- **Serviceföretag**: Schemaläggning för tekniker, konsulter och andra servicepersonal

## Tekniska detaljer
- **Materialiserad sökväg**: Använder en effektiv databasdesign för att hantera hierarkiska relationer
- **Cachning**: Implementerar cachning för behörighetskontroller för förbättrad prestanda
- **Responsiv design**: Frontend-applikationen är fullt responsiv för användning på olika enheter
- **Modulär arkitektur**: Tydlig separation mellan modeller, controllers och vyer
- **Utbyggbar**: Designad för att enkelt kunna utökas med nya funktioner och anpassningar
