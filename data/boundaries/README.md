# Boundary Files

Two files, ready to load into PostGIS. Nothing needs to be downloaded to start building the map.

- `tuguegarao-barangays.geojson`: the 49 barangays of Tuguegarao City
- `cagayan-municipalities.geojson`: the 28 municipalities and 1 city of Cagayan

Prepared on October 3, 2026.

## What is in each file

Both are GeoJSON feature collections in plain longitude and latitude (SRID 4326), with coordinates rounded to 6 decimals.

`tuguegarao-barangays.geojson`, properties per feature:

- `psgc_code`: 10 digits, as a string, for example `0201529001`
- `name`: for example `Annafunan East`
- `municipality_psgc_code`: `0201529000`

`cagayan-municipalities.geojson`, properties per feature:

- `psgc_code`: 10 digits, as a string, for example `0201501000`
- `name`: for example `Abulug`
- `kind`: `city` or `municipality`. Only Tuguegarao City is a city.
- `province_psgc_code`: `0201500000`

The province map contains Cagayan's 28 municipalities and one city only. Batanes and the other Region II provinces are outside this dataset.

Geometry is `Polygon` for most features and `MultiPolygon` for towns with islands. The schema stores `MultiPolygon`, so wrap every geometry with `ST_Multi` when loading.

## Where they came from

**Shapes:** [faeldon/philippines-json-maps](https://github.com/faeldon/philippines-json-maps), 2023 edition, MIT License, by James Faeldon. That project builds its files from [altcoder/philippines-psgc-shapefiles](https://github.com/altcoder/philippines-psgc-shapefiles), which follows PSA's PSGC as of December 31, 2023.

- Barangays: `2023/geojson/municities/hires/bgysubmuns-municity-201529000.0.1.json`, the high resolution file, simplified by that project to 10 percent of the original points.
- Towns: `2023/geojson/provdists/medres/municities-provdist-201500000.0.01.json`, the medium resolution file, simplified to 1 percent. This keeps the province view light at 78 KB.

**Cross-check of codes and names:** [bendlikeabamboo/barangay-boundaries-repository](https://github.com/bendlikeabamboo/barangay-boundaries-repository), release `v2026.4.13.0`, by Manuel Balmeo. It matches NAMRIA boundaries (version 2023-11-06) to PSA's PSGC snapshot of October 24, 2023. All 49 barangay codes and all 29 town codes agree between the two sources, and the source names agree with that project's PSGC names. The local display aliases below are intentional.

## What was changed

Only the properties. The shapes are as published.

1. The 9 digit numeric codes were padded to the 10 digit PSGC form.
2. Properties were renamed to match `docs/engineering/database.md`.
3. Coordinates were rounded to 6 decimals, about 11 centimeters.
4. `Sanchez-Mira` (`0201522000`) was renamed `Sanchez Mira`, the spelling in PSA's First Quarter 2026 PSGC update (see the `2026-04-13` folder of barangay-boundaries-repository).

## Checks that passed

- 49 barangays and 29 towns, every code unique and 10 digits
- Every geometry valid
- No overlaps and no gaps between neighbors in either file
- Barangays total 119.57 square kilometers, which matches the city's area in the NAMRIA source (119.60)

## Why not the shapes from the barangay-boundaries-repository release

That release simplifies every boundary with a tolerance of 0.005 degrees, about 550 meters. Tuguegarao's Centro barangays are only a few hundred meters wide, so each barangay collapses to three to five corners. The result covers only 104 of the city's 119 square kilometers, breaks into 20 separate pieces, and has about 1 square kilometer of overlaps. It is used here for its code matching, not for its shapes.

## Limits to know about

- **These shapes are simplified.** Each barangay has 5 to 20 points. They join cleanly and the areas are right, so they are good for a shaded map. They are not survey boundaries. Say so if asked.
- **The town outline and the barangays use different levels of detail.** Tuguegarao's outline in the town file will not line up exactly with the outer edge of its barangays. Use the town file for the province view and the barangay file after drilling down, not both at once.
- **Codes follow PSGC as of late 2023.** Check the 49 codes and names against PSA's current listing for Tuguegarao City before the pitch.
- **Not included:** urban or rural class and population. Town population and economic figures are in `data/towns/` (see its README). Barangay population comes from PSA's listing and is entered separately.

## License and credit

- Both GitHub projects are under the MIT License. Keep their copyright notices when redistributing.
- The underlying boundaries are credited to NAMRIA and the codes to PSA. The exact terms for the NAMRIA boundaries were not confirmed during research. Read them at the source before the pitch and record the result in `docs/engineering/decisions.md`, O1.

Suggested credit line for the app's footer and the slide deck:

> Boundaries: NAMRIA and PSA, through philippines-json-maps by James Faeldon (MIT License). Codes cross-checked with barangay-boundaries-repository by Manuel Balmeo.

## Getting the files again, or for another town

Git is enough. This fetches only the two files, not the whole repository.

```
git clone --depth 1 --filter=blob:none --sparse https://github.com/faeldon/philippines-json-maps
cd philippines-json-maps
git sparse-checkout set --no-cone "/2023/geojson/municities/hires/bgysubmuns-municity-201529000.0.1.json" "/2023/geojson/provdists/medres/municities-provdist-201500000.0.01.json"
```

For another town, replace `201529000` with that town's code without the leading zero. For example Solana is `201527000`.

## Upgrading to full detail later

Do this only if the simplified shapes look too angular on screen.

1. Get the full detail barangay shapefile, either from `altcoder/philippines-psgc-shapefiles` (it uses Git LFS, so install Git LFS first) or from the Philippines administrative boundaries dataset on the Humanitarian Data Exchange.
2. Filter it to Tuguegarao City and simplify it in mapshaper with the `keep-shapes` option. Mapshaper simplifies shared borders once, so neighbors stay joined. Simplifying each polygon on its own, as PostGIS `ST_Simplify` does, opens gaps.
3. Run the same checks listed above before replacing the file.

## Province-wide barangay population explorer

The Home About the Data explorer now also uses per-town geometry in `data/barangays/maps/`. All 29 towns use the same documented 2023 philippines-json-maps source; each is loaded only when selected. See `data/barangays/README.md` for the 2024 census join, reconciliation, and retained license.

## October 24, 2026 recheck

Compared all 820 barangay geometries in the 29 browser datasets with the published philippines-json-maps files by PSGC code. All feature counts and coordinates match at six decimal places. A PostGIS audit found 820 unique codes, no invalid or empty geometries, and no within-town overlapping pairs larger than one square metre. Tuguegarao's 49 polygons dissolve to one polygon without holes, covering 119.569899 square kilometres. These are source-integrity and topology checks, not an independent cadastral validation or proof that the 2023 boundaries are current.

Use these local display names consistently in the browser dataset, seed boundary file, and population CSV; preserve the source PSGC code and population join:

| PSGC code | PSA source name | Local display name |
| :- | :- | :- |
| 0201529018 | Carig | Carig Sur |
| 0201529050 | Reyes | Carig Norte |
| 0201529031 | Pengue | Pengue-Ruyu |

The [current PSA listing](https://psa.gov.ph/classification/psgc/barangays/0201529000) still uses Carig, Reyes, and Pengue. The [city's ordinance 67-08-2019](https://tuguegaraocity.gov.ph/public/files/issuances/2019/ORDINANCE/Ord.%20No.%2067-08-2019.pdf) explicitly identifies Carig Norte as Reyes. City [resolution 229-08-2020](https://tuguegaraocity.gov.ph/public/files/issuances/2020/RESOLUTION/229-08-2020.pdf) uses Carig Sur and Pengue-Ruyu. This changes display names only; no boundary was moved or population reassigned.
