# WPschemaVUE - Schema Management Plugin

## Översikt
WPschemaVUE är ett WordPress-plugin för hantering av scheman inom organisationer med hierarkisk struktur. Pluginet använder Vue 3 för frontend-gränssnittet och erbjuder både en administratörsdel och en publik del.

## Funktionalitet

### Organisationshantering
- Hierarkisk organisationsstruktur där organisationer kan ha underorganisationer
- Användare kan tillhöra olika organisationer med olika roller
- Behörigheter ärvs nedåt i hierarkin (en användare med behörighet på en högre nivå har samma behörigheter i underorganisationer)

### Behörighetshantering
Pluginet använder tre behörighetsnivåer:
- **Bas**: Kan se alla scheman i sin organisation och redigera sina egna arbetspass
- **Schemaläggare**: Kan se och redigera allas schema inom sin organisation
- **Schemaadmin**: Har alla behörigheter inklusive att skapa resurser/nya scheman

### Teknisk uppbyggnad
- **Frontend**: Vue 3 med Composition API och Pinia för state management
- **Backend**: WordPress PHP-klasser och REST API
- **Databas**: Egna tabeller för organisationer, användare, resurser och scheman
- **Säkerhet**: WordPress cookie-autentisering för att säkra åtkomst till API:et

## Fördelar
1. **Flexibilitet**: Anpassningsbar för olika typer av organisationer och schemaläggningsbehov
2. **Skalbarhet**: Hierarkisk struktur möjliggör hantering av både små och stora organisationer
3. **Användarvänlighet**: Modernt gränssnitt med Vue 3 gör det enkelt för användare att hantera scheman
4. **Säkerhet**: Robust behörighetshantering säkerställer att användare endast kan se och redigera data de har behörighet till

## Användningsområden
- Företag med flera avdelningar
- Sjukhus med olika avdelningar och vårdteam
- Skolor med lärare och klasser
- Restauranger med personal och arbetspass
- Andra organisationer som behöver schemaläggning med hierarkisk struktur
