export function createApi(boot) {
  const base = (boot.restUrl || '').replace(/\/$/, '') + '/mr-ml/v1';
  const wpBase = (boot.restUrl || '').replace(/\/$/, '') + '/wp/v2';
  const headers = {
    'Content-Type': 'application/json',
    'X-WP-Nonce': boot.nonce || '',
  };

  async function req(path, opts = {}) {
    const res = await fetch(base + path, { ...opts, headers: { ...headers, ...(opts.headers || {}) } });
    if (!res.ok) {
      const text = await res.text().catch(() => '');
      throw new Error(`HTTP ${res.status}: ${text || res.statusText}`);
    }
    const ct = res.headers.get('content-type') || '';
    return ct.includes('application/json') ? res.json() : res.text();
  }

  async function wpReq(path, opts = {}) {
    const res = await fetch(wpBase + path, { ...opts, headers: { ...headers, ...(opts.headers || {}) } });
    if (!res.ok) {
      const text = await res.text().catch(() => '');
      throw new Error(`HTTP ${res.status}: ${text || res.statusText}`);
    }
    const ct = res.headers.get('content-type') || '';
    return ct.includes('application/json') ? res.json() : res.text();
  }

  return {
    // Subdomains
    getSubdomains: () => req('/subdomains'),
    createSubdomain: (payload) => req('/subdomains', { method: 'POST', body: JSON.stringify(payload) }),
    updateSubdomain: (id, payload) => req(`/subdomains/${id}`, { method: 'PUT', body: JSON.stringify(payload) }),
    deleteSubdomain: (id) => req(`/subdomains/${id}`, { method: 'DELETE' }),

    // Folders
    getFolders: () => req('/folders'),
    createFolder: (payload) => req('/folders', { method: 'POST', body: JSON.stringify(payload) }),
    updateFolder: (id, payload) => req(`/folders/${id}`, { method: 'PUT', body: JSON.stringify(payload) }),
    deleteFolder: (id) => req(`/folders/${id}`, { method: 'DELETE' }),

    // Mode
    getMode: () => req('/mode'),
    setMode: (mode, mainNoPrefix) => req('/mode', { method: 'POST', body: JSON.stringify({ mode, mainNoPrefix }) }),

    // SEO
    getSeo: () => req('/seo'),
    setSeo: (payload) => req('/seo', { method: 'POST', body: JSON.stringify(payload) }),

    // Audit
    getAuditShortcodes: () => req('/audit/shortcodes'),
    runAuditReindex: () => req('/audit/reindex', { method: 'POST' }),

    // Visibility
    getVisibility: (postId) => req(`/visibility/${postId}`),
    setVisibility: (postId, payload) => req(`/visibility/${postId}`, { method: 'POST', body: JSON.stringify(payload) }),

    // Pages (WordPress REST API)
    createPage: (payload) => wpReq('/multiregional_page', { 
      method: 'POST', 
      body: JSON.stringify({
        title: payload.title,
        slug: payload.slug,
        content: payload.content,
        status: payload.status || 'draft'
      })
    }),
    getPage: (postId) => wpReq(`/multiregional_page/${postId}`),
    updatePage: (postId, payload) => wpReq(`/multiregional_page/${postId}`, {
      method: 'PUT',
      body: JSON.stringify({
        title: payload.title,
        slug: payload.slug,
        content: payload.content,
        status: payload.status
      })
    }),
    
    // Page SEO
    getPageSeo: (postId) => req(`/page-seo/${postId}`),
    setPageSeo: (postId, payload) => req(`/page-seo/${postId}`, {
      method: 'POST',
      body: JSON.stringify(payload)
    }),
  };
}


