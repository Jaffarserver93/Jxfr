(function (global) {
  'use strict';

  const COOREN_BASE_URL = 'https://coorenlabs-1iap.onrender.com';
  const API_PREFIX = '/anime/animekai';

  function buildUrl(path, params) {
    const url = new URL(`${COOREN_BASE_URL}${API_PREFIX}${path}`);
    if (params && typeof params === 'object') {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          url.searchParams.set(key, String(value));
        }
      });
    }
    return url.toString();
  }

  async function request(path, params) {
    const response = await fetch(buildUrl(path, params), {
      method: 'GET',
      headers: {
        Accept: 'application/json'
      }
    });

    if (!response.ok) {
      const text = await response.text();
      throw new Error(`Cooren API request failed (${response.status}): ${text || response.statusText}`);
    }

    return response.json();
  }

  const animeService = {
    baseUrl: COOREN_BASE_URL,

    // Search and discovery
    search(query) {
      return request(`/search/${encodeURIComponent(query)}`);
    },
    spotlight() {
      return request('/spotlight');
    },
    schedule(date) {
      return request(`/schedule/${encodeURIComponent(date)}`); // YYYY-MM-DD
    },
    suggestions(query) {
      return request(`/suggestions/${encodeURIComponent(query)}`);
    },

    // Home page lists
    recentEpisodes() {
      return request('/recent-episodes');
    },
    recentAdded() {
      return request('/recent-added');
    },
    completed() {
      return request('/completed');
    },
    newReleases() {
      return request('/new-releases');
    },

    // Browsing categories
    movies() {
      return request('/movies');
    },
    tv() {
      return request('/tv');
    },
    ova() {
      return request('/ova');
    },
    ona() {
      return request('/ona');
    },
    specials() {
      return request('/specials');
    },
    genres() {
      return request('/genres');
    },
    genre(genre) {
      return request(`/genre/${encodeURIComponent(genre)}`);
    },

    // Detail and watch
    info(id) {
      return request('/info', { id }); // query param required by API
    },
    watch(episodeId, dub) {
      return request(`/watch/${encodeURIComponent(episodeId)}`, { dub });
    },
    servers(episodeId, dub) {
      return request(`/servers/${encodeURIComponent(episodeId)}`, { dub });
    }
  };

  global.animeService = animeService;
})(window);
