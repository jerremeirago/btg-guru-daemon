# BTS Guru Daemon Service - Data Models

## Database Schema

This document outlines the database schema for the BTS Guru Daemon Service, including tables, relationships, and key fields.

### User-Related Models

#### Users Table
- Standard Laravel users table with extensions for API access
- Fields:
  - `id`: Primary key
  - `name`: User's name
  - `email`: User's email address
  - `password`: Hashed password
  - `subscription_tier`: User's subscription level (basic, premium, enterprise)
  - `created_at`: Timestamp of account creation
  - `updated_at`: Timestamp of last update

#### API Keys Table
- Stores API keys for authentication
- Fields:
  - `id`: Primary key
  - `user_id`: Foreign key to users table
  - `name`: Name of the API key
  - `key`: Hashed API key
  - `last_used_at`: Timestamp of last usage
  - `expires_at`: Expiration date (nullable)
  - `created_at`: Timestamp of creation
  - `updated_at`: Timestamp of last update

#### API Usage Table
- Tracks API usage for rate limiting and billing
- Fields:
  - `id`: Primary key
  - `user_id`: Foreign key to users table
  - `endpoint`: API endpoint accessed
  - `method`: HTTP method used
  - `status_code`: Response status code
  - `response_time`: Time taken to process request (ms)
  - `created_at`: Timestamp of request

### Sports Data Models

#### Sports Table
- List of supported sports
- Fields:
  - `id`: Primary key
  - `name`: Sport name
  - `slug`: URL-friendly identifier
  - `polling_frequency`: Default polling frequency in seconds
  - `active`: Boolean indicating if sport is active
  - `created_at`: Timestamp of creation
  - `updated_at`: Timestamp of last update

#### Leagues Table
- Sports leagues/competitions
- Fields:
  - `id`: Primary key
  - `sport_id`: Foreign key to sports table
  - `name`: League name
  - `slug`: URL-friendly identifier
  - `country`: Country code
  - `logo_url`: URL to league logo
  - `current_season`: Current season identifier
  - `active`: Boolean indicating if league is active
  - `created_at`: Timestamp of creation
  - `updated_at`: Timestamp of last update

#### Teams Table
- Sports teams
- Fields:
  - `id`: Primary key
  - `sport_id`: Foreign key to sports table
  - `name`: Team name
  - `slug`: URL-friendly identifier
  - `short_name`: Abbreviated team name
  - `logo_url`: URL to team logo
  - `country`: Country code
  - `created_at`: Timestamp of creation
  - `updated_at`: Timestamp of last update

#### Matches Table
- Individual sports matches/games
- Fields:
  - `id`: Primary key
  - `sport_id`: Foreign key to sports table
  - `league_id`: Foreign key to leagues table
  - `home_team_id`: Foreign key to teams table
  - `away_team_id`: Foreign key to teams table
  - `status`: Match status (scheduled, live, completed, postponed)
  - `start_time`: Scheduled start time
  - `home_score`: Current/final home team score
  - `away_score`: Current/final away team score
  - `period`: Current period/quarter/half
  - `time`: Current time in the match
  - `venue`: Venue name
  - `rapidapi_id`: ID from RapidAPI for reference
  - `last_updated`: Timestamp of last data update
  - `created_at`: Timestamp of creation
  - `updated_at`: Timestamp of last update

#### Match Events Table
- Significant events within a match
- Fields:
  - `id`: Primary key
  - `match_id`: Foreign key to matches table
  - `team_id`: Foreign key to teams table (nullable)
  - `player_name`: Name of player involved (if applicable)
  - `event_type`: Type of event (goal, card, substitution, etc.)
  - `time`: Time of event in the match
  - `description`: Additional event details
  - `created_at`: Timestamp of creation

### Caching and Monitoring Models

#### Cache Metadata Table
- Tracks cache information for monitoring
- Fields:
  - `id`: Primary key
  - `cache_key`: Redis cache key
  - `entity_type`: Type of entity (sport, league, match)
  - `entity_id`: ID of the entity
  - `ttl`: Time-to-live in seconds
  - `expires_at`: Expiration timestamp
  - `created_at`: Timestamp of creation
  - `updated_at`: Timestamp of last update

#### API Request Logs Table
- Logs of requests to RapidAPI
- Fields:
  - `id`: Primary key
  - `endpoint`: RapidAPI endpoint
  - `parameters`: Request parameters (JSON)
  - `response_code`: Response status code
  - `response_time`: Time taken for response (ms)
  - `cache_hit`: Boolean indicating if request was served from cache
  - `created_at`: Timestamp of request

## Relationships

- Users have many API Keys (one-to-many)
- Users have many API Usage records (one-to-many)
- Sports have many Leagues (one-to-many)
- Sports have many Teams (one-to-many)
- Leagues have many Matches (one-to-many)
- Teams belong to many Matches (many-to-many)
- Matches have many Match Events (one-to-many)
- Matches belong to a Sport (many-to-one)
- Matches belong to a League (many-to-one)
- Match Events belong to a Match (many-to-one)
- Match Events may belong to a Team (many-to-one, nullable)
