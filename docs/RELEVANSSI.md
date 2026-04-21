# Relevanssi (recherche front)

Relevanssi is a **separate** WordPress plugin. Install it from Add Plugins and activate it. **WordPress Is… Core** does not bundle it; when it is present, a small integration removes internal `_wpis_*` custom fields from the search index (title, post body and public taxonomies still index as configured in Relevanssi).

## Checklist (admin)

1. **Relevanssi → Indexing**  
   - Under **Post types**, enable **quote** (and any page types you still want, for example *page* for “About” if you need them in the same search).  
   - Rebuild the index: **Relevanssi → Indexing** → **Build the index** (or the admin notice if offered).

2. **Taxonomies**  
   - Ensure **claim_type** and **sentiment** are included if you want them to count toward a match (Relevanssi default is to index visible tax terms when those options are on).

3. **After changing themes or the quote template**  
   - Run a full rebuild again if search feels stale.

4. **Polylang** (if you use it)  
   - Follow Relevanssi’s multilingual note in their docs: usually one index per language or their compatibility settings.

## Theme

The [wpis-theme](https://github.com/jaz-on/wpis-theme) `search.html` template is built for a block Query with **Inherit** from the main search query (the same one Relevanssi amends). The results area uses the **quote feed card** part so hits line up with the home feed. If you also index *pages*, those hits still work but the card is optimized for the `quote` type.

## Code reference

- `src/Search/RelevanssiIntegration.php`  
- [Relevanssi filter `relevanssi_index_custom_fields`](https://www.relevanssi.com/user-manual/filter-hooks/relevanssi_index_custom_fields/)
