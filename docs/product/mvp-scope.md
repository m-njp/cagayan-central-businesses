# Cagayan Business: Product Description and MVP Scope

Oct 3, 2026, @Niño Justin  Marcos

## Product description

Cagayan Business is a map-based dashboard that gives the Provincial Government of Cagayan one picture of registered businesses, barangay by barangay, and gives the public a guide to starting a business that draws on the same data.

- **For the Capitol:** see the status of registered businesses and the direction of the local economy, barangay by barangay: how many businesses, what kinds, how many are new, renewing, or closing, and which areas have fewer businesses than similar areas.
- **For the public:** explore what businesses exist where, get a personal checklist of what is needed to start one, and tell the Capitol what kind of business they wish their area had.
- **MVP scope:** Tuguegarao City only, all 49 barangays, built so other towns can be added without a redesign.
- **MVP data:** real barangay boundaries and population, with simulated business records that are labeled as simulated on every screen.

One-line version for slides: "One map of Cagayan's registered businesses, for the planners who decide and the people who want to start."

## October 24, 2026 clarification: Revenue and Economic Status

Keep the project's business and economic information purpose. Do not pivot it into an auditing system. The approved direction is to retain government revenue as one clearly identified contextual measure alongside broader economic indicators; revenue alone is not a definition or score of a place's economy.

| Information group | Meaning and candidate measures, subject to verified availability |
| :- | :- |
| Government Finances | City/municipal government revenue, source-defined expenses, and comparable annual changes. COA accounts describe the reporting government, not private business sales. |
| Economic Activity | GDP, real GDP growth, and industry composition from PSA. GDP describes economic output; growth at constant prices adjusts for price changes. |
| Household Conditions | Family income/expenditure, employment, and poverty, with survey coverage and reference periods preserved. |
| Business Landscape | Registered businesses by location and category, openings, renewals, and closures, when records and metrics exist. |
| Community Interests | Wishes expressed by participating respondents, not measured market demand. |

Government revenue can change because of national transfers, collection policy, or other factors as well as local activity. It must not be treated as an automatic indicator of business growth, household prosperity, or investment suitability. Use an explicit label such as City Government Revenue where supported by the source. Economic Status is a grouping of defined indicators, not an invented good/bad rating.

Keep every figure at its actual geographic level. The PSA Provincial Product Accounts tables located for this project report provinces/HUCs; Cagayan Province's GDP must not be displayed as Tuguegarao City's or a barangay's GDP. No geographic allocation or substitution is authorized without an appropriate source and method. Keep government accounts separate from economic output and household measures, even when they appear alongside each other.

Official references: [PSA PPA definitions](https://psa.gov.ph/statistics/ppa/faqs) and [BLGF explanation of local and external government income](https://blgf.gov.ph/dof-unit-clarifies-lgu-income-data/). The historical COA view remains future work in the roadmap. Further COA/BOI/PSA document collection is deferred to a separate session; this clarification records the direction and does not implement new screens or import new figures.

## Problem

The Capitol can see yearly, town-level business indicators from national sources, but it has no view of its own that shows registered businesses barangay by barangay, by type, and over time. The research behind this statement is in the Verification findings section, and the team should still confirm it with the provincial planning office.

- **Planners decide without barangay-level evidence.** Livelihood programs, training, and support are placed without knowing where businesses are thin, growing, or closing.
- **People start businesses blind.** An aspiring owner does not know how many similar businesses already operate nearby, or what registration requires for their specific case. The cost is savings spent in a crowded spot and repeat trips for missing documents.
- **Citizens have no channel to say what is missing.** A resident who wishes their town had a pharmacy, a hardware store, or a bank has nowhere to record it, so that demand never reaches planners or entrepreneurs.
- **Existing tools answer other questions.** City permit systems process applications. National indexes rank towns once a year. Map apps show establishments but not registration, trends, or comparisons between barangays.

## Users

The Capitol is the primary user; the public is the secondary user.

1. **Capitol administrators, planners, and program officers.** They log in, see every measure including total declared sales per area, open the full record of a business including the owner's name and contact number, and export summaries for reports and proposals.
2. **Aspiring business owners and the general public.** No login. They explore the map, see each business's type, short description, and year established, use the start a business guide, and submit wishes for businesses their area lacks.
3. **City and municipal offices (after the MVP).** The permit office and treasurer supply the records and see their own area.

## EGAY pillar and judging fit

The project anchors in Good Governance, which the challenge describes as tools that promote transparency, streamline government services, or improve citizen engagement.

How it answers each criterion, 20% each:

- **Relevance to PGC and EGAY:** a barangay-level view of business activity across the province is something only the province needs, because each town sees only its own records.
- **Innovation and creativity:** survival rates computed from permit renewals, an outlook that is tested against past years, sales totals protected by disclosure rules, citizen wishes used as a demand signal, and one dataset serving both planners and the public.
- **Technical feasibility:** standard web mapping and a database. Nothing experimental.
- **Impact to government and citizens:** programs aimed with evidence, citizens heard on what their area lacks, and fewer people starting a business without information.
- **Quality of presentation and demo:** a map is easy to follow in a five-minute pitch.

Supporting pillar: Youth, through employment and livelihood. Keep this as one sentence in the pitch, not a second anchor.

## MVP features: Capitol dashboard

The Capitol side is built around one question from the Governor: how is business doing across my province, where is the money, and where is it heading? Fourteen features answer it.

1. **Province at a glance.** One screen with the towns on a map, shaded by total declared business sales, number of businesses, or growth. Headline numbers sit beside it: active businesses, new, renewed, not renewed, net growth, total declared sales, and the change from last year. In the MVP Tuguegarao is filled in and the other towns are greyed out as "not yet connected".
2. **Drill down.** Click a town to see its barangays shaded the same way. Click a barangay to open its profile.
3. **Barangay profile.** Registered businesses by type and size, population, businesses per 1,000 residents, total declared sales, sales per business, share of the city's total, and change from last year.
4. **Rankings and movers.** Sort barangays or towns by declared sales, business count, density, or growth. See which rose or fell in rank since last year.
5. **Dominance.** For each area: the leading business type by count and by share of sales, how concentrated the area is in that one type, and what it specializes in compared with the rest of the city.
6. **Compare.** Put two to four barangays or towns side by side on every measure.
7. **Status and growth trends.** Every business carries a status for the year: new, renewed, not renewed, or closed. Trends show these per year, by barangay and by business type.
8. **Survival rate.** The share of businesses that renewed one year and two years after first registering, by business type.
9. **Outlook.** A projection of next year's active businesses and declared sales for each barangay, shown as a range. It is built from survival rates and registration trends, and it shows how close the same method came when tested on past years.
10. **Early warning.** Flags barangays or business types where non-renewals have risen two years in a row, or where the outlook points down.
11. **Peer comparison.** Barangays compared with similar barangays, urban with urban and rural with rural. The label is "below peer average", not "underperforming".
12. **Citizen wishes.** What residents say their town lacks, counted by business type for the whole town, shown beside what is actually registered there.
13. **Business directory (admin only).** A searchable list of businesses. Opening one shows its full record, including the owner's name and contact number, so program officers can reach owners. Every view of a full record is logged.
14. **Filters, export, and brief.** Filter by type, size, status, and year. Export data as CSV, or a one-page economic brief of any barangay or town as PDF.

## MVP features: public side

The public side has five features and needs no login.

1. **Explore map.** The same barangay map, with declared sales shown only as a band: how many registered businesses, what types, and the most common types in each barangay.
2. **Business listing.** For each business the public sees only its name, type, a brief description, the year it was first established, and its barangay. No owner name, no contact number, no income.
3. **I wish there was this business here.** A resident picks their town and may choose a business type, name a specific business, or do both. They can add a short reason of up to 255 characters. Wishes are counted, not treated as proof of demand.
4. **Start a business in Cagayan.** Four questions produce a personal checklist in the order to do things, with where to get each document. Every item links to its official source and shows the date it was last verified. The guide is read-only: it shows what to prepare and where to get it, with no forms, uploads, or submission. The application itself stays in the city's own online system.
   - Ownership: solo owner, partnership or corporation, or cooperative
   - Activity: food, retail, services, manufacturing, and others
   - Premises: storefront, home-based, or online only
   - Employees: none or with staff
5. **Local snapshot.** After choosing a business type and a barangay, the person sees how many similar registered businesses are already there, the survival rate for that type in the city, and how many residents of the town wished for that type.

For the MVP, cover three complete paths end to end, for example a solo-owned coffee shop with a storefront, a home-based online seller, and a service business with employees. Three verified paths are better than twenty unverified ones.

## Out of scope for the MVP

These are left out on purpose, and each has a reason the team can give if a judge asks.

- **Permit application, issuance, renewal, and expiry warnings.** This is a city and municipal function, and LGUs were already directed to run electronic one-stop shops for it. Cagayan Business shows permit compliance only as a statistic, such as the renewal rate per barangay. Clarified "26-10-28" (D39, user decision): a later business registry may store and publish permit validity, the renewal range, expiry and uploaded documents as records for the Capitol. It still does not take applications, compute or collect fees, or issue and renew permits, which stay with the city. Built "26-10-29" in [Business Registry Records](../plan/active/26-10-28-business-registry-records.md) (D40): records, permits, documents and a permit status label (M32). Expiry is a label on the record, not a warning or a notification.
- **Sales of any single business on screen.** Each business's declared sales are stored and used in the calculations, but the dashboard only ever displays area totals.
- **Unregistered businesses.** The dashboard covers registered businesses only, and says so on screen.
- **Black-box forecasts.** The outlook uses methods that can be explained in one sentence and tested against past years. A model that scores each business's chance of renewing comes after real data is available.
- **Other municipalities.** The structure is ready for them; their data comes later.
- **An AI assistant.** Asking the map questions in Filipino or Ilocano is a later addition, not part of the core.

## Data plan

The frame is real and the business records are simulated. Every screen that shows simulated records carries a visible "Simulated data" label, and no pattern in them is presented as a finding about Tuguegarao.

**Real in the MVP**

- Barangay names, urban or rural class, and 2024 census population for all 49 barangays, from the [PSA geographic code listing for Tuguegarao City](https://psa.gov.ph/classification/psgc/barangays/0201529000). PSA content is published under a Creative Commons attribution license, so cite it.
- Barangay boundary shapes. The team still has to choose a source, check its license, and cite it.
- Checklist content for the start a business guide, taken only from official sources and verified by a team member.

**Simulated in the MVP**

- Business records, generated to follow the fields of the city's business permit application form so real data can replace them later without a rebuild.

**Fields per business record**

- **Public fields:** business name, business type (grouped using the Philippine Standard Industrial Classification), brief description, year first established, barangay, map coordinates
- **Admin-only fields:** owner name, contact number, ownership type, size category (micro, small, medium), number of employees
- **Status per year:** new, renewed, not renewed, or closed
- **Declared gross sales per year:** never shown for one business, used only inside area totals
- **Citizen wish record:** town, optional business type, optional specific business name, optional short reason, date. The form requires a type or a specific business, or both. No resident name or contact is collected.

**Privacy rules built into the product**

- **Declared sales are used in full, shown in total.** The system stores each business's declared gross sales and adds them up. The Capitol sees the total for a barangay or town as a figure. The public sees only a band, such as "50 to 100 million pesos".
- **Small-group rule.** An area or type with fewer than 5 businesses shows no sales figure, because the total would reveal too much about each one.
- **Dominance rule.** If one business makes up most of an area's total, for example more than 70 percent, the figure is hidden or merged with a neighboring group. The team sets the exact threshold with the adviser.
- **No single business's sales are ever displayed,** not even to administrators.
- Owner name and contact number are visible to logged-in Capitol administrators only, for the stated purpose of reaching owners about programs. The public side never shows them.
- Every time an administrator opens a full business record, the system logs who and when.
- With real data, the city and the Capitol sign a data sharing agreement first, and only the fields the Capitol needs are shared. The legal basis is in the Verification findings section.

**Path to real data:** a request letter through the adviser or dean to the Tuguegarao permit office and City Treasurer, asking for records or, if that is refused, for totals per barangay and business type.

## Metric definitions

Every number on screen has a plain definition the team can state in the question and answer round.

- **Business status:** new (first registered this year), renewed, not renewed, or closed (formally retired).
- **Net growth:** new registrations minus non-renewals and closures in a year, as a number and as a percentage of last year's active businesses.
- **Business density:** registered active businesses divided by population, times 1,000.
- **Total declared sales:** the sum of gross sales declared by all registered businesses in an area for the year. It measures declared business activity, not the income of residents or of the barangay government.
- **Sales per business and share of total:** total declared sales divided by the number of businesses, and the area's total as a percentage of its city or province.
- **Rank change:** an area's position this year compared with last year on a chosen measure.
- **Dominant type:** the business type with the largest share in the area, measured by count and separately by declared sales.
- **Concentration:** the dominant type's share of the area's total. A high value means the area depends on one kind of business.
- **Specialization:** a type's share in the barangay divided by the same type's share in the whole city. Above 1 means the barangay has more of that type than is typical.
- **Renewal rate for a year:** businesses active the previous year that renewed this year, divided by all businesses active the previous year.
- **One-year survival rate:** businesses first registered in a year that renewed the following year, divided by all businesses first registered in that year. The two-year rate uses the second year after.
- **Non-renewal:** a business that did not renew. It usually means closure, but it can also mean the business moved or kept operating without a permit. Say this on screen.
- **Outlook, businesses:** this year's active businesses times the renewal rate for each type, plus expected new registrations based on the last three years. The low and high ends use the lowest and highest rates seen in past years.
- **Outlook, sales:** projected businesses of each type times that type's average declared sales, adjusted by its recent growth.
- **Backtest error:** the outlook method applied to past years and compared with what actually happened, shown as an average percentage error beside every projection.
- **Early warning:** non-renewals rising two years in a row, or an outlook whose high end is below this year's figure.
- **Public sales band:** the band that contains an area's total declared sales. The team sets the band edges with the adviser.
- **Peer group:** barangays of the same urban or rural class.
- **Below peer average:** business density lower than the peer group's median by a margin the team sets and states.
- **Wish count:** the number of wishes for a business type, a specific company, or both in a town. It shows interest from the people who chose to answer, not measured demand.

## Suggested build approach

A standard web application is enough. These are suggestions; the team should pick the tools it already knows.

- **Front end:** a web framework such as React, a map library such as Leaflet or MapLibre, and a chart library.
- **Back end:** one API in whatever the team is fastest with, for example Laravel or FastAPI.
- **Database:** PostgreSQL with the PostGIS extension, which stores boundaries and answers "which barangay is this point in".
- **Data generator:** a script that creates the simulated records with a fixed seed, so the demo shows the same numbers every time.
- **Roles:** Capitol user with login, and public with no login.

A workable split for three members:

1. Map and front end
2. API, database, and the metric calculations
3. Data (boundaries, population, generator) and the verified checklist content

## Demo walkthrough

Ten steps cover both sides in under five minutes.

1. Log in as a Capitol administrator and open Province at a glance. Read the headline numbers and point to the "Simulated data" label, saying what is real: boundaries and population.
2. Click Tuguegarao and shade its barangays by total declared sales. Open Rankings: the top five barangays and the biggest movers since last year.
3. Open one barangay's profile and its Dominance panel: the leading business type, how concentrated the barangay is, and what it specializes in.
4. Compare two barangays side by side.
5. Open a very small barangay to show the sales figure hidden by the small-group rule.
6. Open Outlook: next year's projected range for a barangay, with the backtest error beside it. Show one Early warning flag.
7. Show Citizen wishes beside Peer comparison, open one business record to show the admin-only owner name and contact number, and export a one-page brief.
8. Switch to the public side. Explore the map with sales shown only as bands, and open a business listing: name, type, description, year established.
9. Submit a wish for a business type in a town and show the count update.
10. Run the start a business guide for a coffee shop, then close on the province view: the greyed-out towns are where the project grows next.

## Risks and items to verify

The concept is sound; the open risks are about evidence and data, and each one has a task.

**To verify before the pitch**

- [ ] Existing systems: researched on October 3, 2026, see Verification findings. Still ask the provincial planning office what view of businesses they have today. Their answer is the problem statement and the traction.
- [ ] Ask the Tuguegarao permit office which system their new online application runs on, whether it has its own reports or map, and whether records can be exported or shared. This decides how Cagayan Business connects to it.
- [ ] Request a note of interest from the Tuguegarao permit office or the provincial planning office. Simulated data is acceptable for the prototype.
- [ ] Choose the barangay boundary source, check its license, and cite it.
- [ ] Checklist sources: the city's requirement list is now in hand, see Verification findings. Save the link and date of the permit office announcement so it can be cited, and add the SEC and CDA sources.
- [ ] Law on income and owner data: researched, see Verification findings. Still ask the adviser or the City Legal Office about figures declared to the city treasurer.
- [ ] List existing and competing solutions for the video, using the systems named in Verification findings.
- [ ] Set the income band edges and the peer comparison margin.

**Risks to state openly**

- **Parts of this already exist.** Do not claim nothing like it exists. Claim the specific gap: barangay level, by business type, year on year, run by the province, with citizen wishes.
- **Declared sales are not true income.** Businesses often declare less than they earn, and a branch may file where its head office is. Present the totals as declared business activity and use them to compare areas and years, not as exact wealth.
- **An outlook on simulated data proves the method, not the accuracy.** Say so in the demo. Real accuracy comes from the backtest once real records are loaded.
- **Owner names and contact numbers are personal data for solo owners.** Keep them admin-only, logged, and covered by an agreement with the city.
- **Wishes are opinions, not demand.** People who answer are not a sample of the town. Show counts honestly and guard against repeat submissions.
- **"Below peer average" can be read as blame.** Present it as where support is needed.
- **The informal sector is invisible.** Registered businesses only, said on screen.
- **Scope creep toward permit processing.** That would duplicate city systems.

## Verification findings

Researched on October 3, 2026. Pieces of this idea exist at national and town level, but no source found shows a province-run, barangay-level view of registered businesses for Cagayan. This is research, not legal advice.

**1. Do such systems already exist?**

- **Tuguegarao City now has its own online application system.** The city permit office held an orientation on its new Online Business Application System on September 9, 2026, according to a barangay announcement the team collected. Applicants fill in their business details, mark the exact location on a map, and enter a line of business. This means the city is now collecting located, typed business records, which is exactly the data this dashboard reads.
- **Permit processing exists nationally too.** DICT's iBPLS lets LGUs process business permits electronically, and LGUs were told to automate by 2021 (Anti-Red Tape Authority, 2020).
- **A national business databank was built.** The Philippine Business Data Bank consolidates public business data from the CDA, DTI, SEC, LGUs, and the fire bureau (National Privacy Commission, 2017). Its current status was not confirmed.
- **Town-level indicators exist.** The Cities and Municipalities Competitiveness Index gathers business registrations and active establishments for every city and municipality each year (Department of Trade and Industry, n.d.-c).
- **Establishment counts exist.** The PSA releases counts of establishments by municipality on request (Philippine Statistics Authority, 2024).
- **Cagayan has an investor site.** DTI Cagayan runs an Investment Information System that compares investment information across LGUs (Region 2 Investment Website, 2025).
- **Business mapping exists for single LGUs.** A commercial product maps every business in an LGU and tracks registration compliance (Business Mapper, n.d.), and a state university built a map of establishments for a town's fire bureau (Manun-og et al., 2022).
- **What was not found:** one tool that shows a whole province barangay by barangay, by business type, with year-on-year survival and citizen wishes. That is the gap to claim.
- **How to position it:** the city system takes in and processes applications. Cagayan Business reads the resulting records and shows what they mean across barangays and, later, across towns. It sits on top of the city system and does not compete with it.

**4. Are the checklist sources official?**

- **Business name:** DTI's own page covers registration for solo owners (Department of Trade and Industry, n.d.-a).
- **Tax registration:** the BIR registration form lists its documentary requirements (Bureau of Internal Revenue, 2025).
- **Local permit:** DTI itself says every business must register with the permit office of its LGU (Department of Trade and Industry, 2022). Service standards come from the Ease of Doing Business law (Republic Act No. 11032, 2018).
- **Tuguegarao City requirements for a new business,** from the city permit office's announcement collected by the team. Files must be JPG, JPEG, PNG, or PDF.
  1. DTI certificate, or CDA or SEC registration, depending on the type of business
  2. Barangay business certificate
  3. Owner's cedula
  4. For corporations: cedula of the authorized representative or owner, and the corporate cedula
  5. Locational or zoning clearance
  6. Photo of the business establishment
  7. Owner's valid ID
  8. Trash bin and signage certification, or a certificate of exemption, from the city environment office (CENRO)
  9. Occupancy permit, building annual clearance, or fire safety inspection certificate
  10. Official receipt of the barangay clearance
- **If a representative applies:** special power of attorney, the representative's valid ID, and a secretary's certificate for corporations.
- **Application statuses in the city system:** for evaluation, endorsing office assessment, license assessment, then for payment. Payment is online or at the City Treasurer's Office, and the permit and business plate are claimed at the permit office.
- **Still missing:** the link and date of the announcement so it can be cited, the city's formal Citizen's Charter, and the SEC and CDA sources.

**5. What does the law allow on income and owner data?**

- **Tax returns:** the tax code bars BIR personnel from divulging a taxpayer's business or income information (Republic Act No. 10021, 2010). This binds the BIR. No source was found on figures declared to a city treasurer, so ask the City Legal Office.
- **Area totals are already reported.** The national competitiveness index collects the total gross sales of registered firms in each city and municipality from the local treasurer (Department of Trade and Industry, n.d.-b). A total for a barangay follows the same idea at a smaller scale, which is why the small-group and dominance rules matter.
- **Company data is not personal data.** Data about corporations and cooperatives is outside the Data Privacy Act (National Privacy Commission, 2017).
- **Solo owner data can be personal data.** For sole proprietorships, the business name, address, contact number, and owner name may identify a person (National Privacy Commission, 2017; Republic Act No. 10173, 2012).
- **Government may process it for its mandate, within limits.** Only the minimum needed, for a stated purpose, with security measures in place (National Privacy Commission, 2017).
- **Sharing between the city and the Capitol should be written down** in a data sharing agreement or similar document (National Privacy Commission, 2020, 2025).
- **A related request was not granted.** In 2022 DTI did not release a list of Tuguegarao businesses with owner names and contact numbers, and pointed the requester to the city permit office (Department of Trade and Industry, 2022).
- **What this means for the design:** the Capitol sees sales as area totals, the public sees bands, and no single business's sales are displayed. Owner name and contact number stay admin-only and logged.

## After the MVP

Growth follows the data, one step at a time.

1. Replace simulated records with real Tuguegarao data under a data sharing agreement.
2. Add municipalities one at a time, starting with the towns nearest the city.
3. Give city and municipal offices their own logins and views.
4. Add layers that explain the map: roads, markets, schools, and agricultural enterprises.
5. Add a question box that answers in Filipino or Ilocano using only the dashboard's own data.

## References

Anti-Red Tape Authority. (2020, September 15). *ARTA to LGUs: Automate business and building permits now using DICT's iBPLS*. https://arta.gov.ph/press-releases/arta-to-lgus-automate-business-and-building-permits-now-using-dicts-ibpls/

Bureau of Internal Revenue. (2025). *Application for registration: For self-employed (single proprietor/professional), mixed income individuals, non-resident alien engaged in trade/business, estate and trust* (BIR Form No. 1901, October 2025 ENCS). https://bir-cdn.bir.gov.ph/BIR/pdf/1901%20October%202025%20ENCS%20Final.pdf

Bureau of Internal Revenue registration display rules, researched October 29, 2026 from secondary summaries for D40; a team member still has to check the official texts on bir.gov.ph and record the verification date. RR No. 15-2024 requires the COR at each place of business (Aureada Law, https://www.aureadalaw.com/post/navigating-revenue-regulations-15-2024-what-online-and-brick-and-mortar-businesses-need-to-know). RMC No. 38-2026 created the Registration Seal Badge with a QR code that verifies the COR (Grant Thornton Philippines, https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/bir-prescribes-mandatory-display-of-registration-seal-badge-for-online-businesses/). RMC No. 113-2026 suspended its mandatory online posting (BusinessMirror, October 27, 2026, https://businessmirror.com.ph/2026/10/27/bir-suspends-seal-requirement-for-online-sellers/).

Business Mapper. (n.d.). *Increasing LGU revenues through tax mapping in the Philippines*. Retrieved October 3, 2026, from http://www.businessmapper.biz/technical-blogs-3/increasing-lgu-revenues-through-tax-mapping-in-the-philippines

Department of Trade and Industry. (n.d.-a). *Business registration and permits*. Retrieved October 3, 2026, from https://www.dti.gov.ph/dti-business-center/dti-business-registration-permits

Department of Trade and Industry. (n.d.-b). *CMCI manual*. Cities and Municipalities Competitiveness Index. Retrieved October 3, 2026, from https://cmci.dti.gov.ph/downloads/forms/CMCI%20Manual.pdf

Department of Trade and Industry. (n.d.-c). *Indicators*. Cities and Municipalities Competitiveness Index. Retrieved October 3, 2026, from https://cmci.dti.gov.ph/about-indicators.php

Department of Trade and Industry. (2022, August 30). *List of SMEs in Tuguegarao City, Cagayan Valley* \[Response to freedom of information request\]. eFOI. https://www.foi.gov.ph/requests/list-of-smes-in-tuguegarao-city-cagayan-valley-dti-957418696822/

Manun-og, M. B., Manun-og, M. R., Wales, A. R. F., Balili, D. A., & Togonon, J. N. (2022). Development of a records management system with GIS integration: Enabling tool for disaster risk management. *SciEnggJ, 15*(2), 72–78. https://scienggj.org/2022/SciEnggJ%202022-vol15-no02-p72-78-Manun-og%20et%20al.pdf

National Privacy Commission. (2017, July 18). *Re: Philippine Business Data Bank* (Advisory Opinion No. 2017-020). https://privacy.gov.ph/wp-content/uploads/2022/01/NPC\_AdvisoryOpinionNo.\_2017-020.pdf

National Privacy Commission. (2019). *Request for list of business industries and the names of registered businesses in each industry in Sorsogon City for research purposes* (Advisory Opinion No. 2019-030). https://privacy.gov.ph/wp-content/uploads/2023/05/2019-Compendium\_rev-2-Single-1.pdf

National Privacy Commission. (2020, December 23). *Data sharing agreements* (NPC Circular No. 2020-03). https://privacy.gov.ph/wp-content/uploads/2021/01/Circular-Data-Sharing-Agreement-amending-16-02-21-Dec-2020-clean-copy-FINAL-LYA-and-JDN-signed-minor-edit.pdf

National Privacy Commission. (2025). *Clarification on certain provisions of NPC Circular No. 2020-03 on data sharing agreements* (NPC Advisory No. 2025-01). https://privacy.gov.ph/wp-content/uploads/2025/07/SGD-2025-01-DSA-Clarification.pdf

Philippine Statistics Authority. (n.d.). *Tuguegarao City* \[Philippine Standard Geographic Code barangay listing\]. Retrieved October 3, 2026, from https://psa.gov.ph/classification/psgc/barangays/0201529000

Philippine Statistics Authority. (2024, December 4). *Number of establishments by municipality* \[Response to freedom of information request\]. eFOI. https://www.foi.gov.ph/agencies/psa/number-of-establishments-by-municipality/

Region 2 Investment Website. (2025, December 3). *Cagayan*. https://r2invest.rdc2.gov.ph/?page\_id=3476

Republic Act No. 10021. (2010). *An act to allow the exchange of information by the Bureau of Internal Revenue of tax matters pursuant to internationally-agreed tax standards, amending Sections 6(F), 71 and 270 of the National Internal Revenue Code of 1997, as amended, and for other purposes*. Lawphil. https://lawphil.net/statutes/repacts/ra2010/ra\_10021\_2010.html

Republic Act No. 10173. (2012). *Data Privacy Act of 2012*. Official Gazette. https://www.officialgazette.gov.ph/2012/08/15/republic-act-no-10173/

Republic Act No. 11032. (2018). *Ease of Doing Business and Efficient Government Service Delivery Act of 2018*. Official Gazette. https://www.officialgazette.gov.ph/2018/05/28/republic-act-no-11032/

Gap: the Tuguegarao permit office announcement and the barangay orientation notice were supplied by the team and are not listed until their links and dates are recorded. No SEC or CDA registration source, no formal Tuguegarao City Citizen's Charter, and no law or ruling on the confidentiality of gross sales declared to a city treasurer were found. The National Privacy Commission's 2019 opinion on listing registered businesses is cited by title only and still needs to be read in full. The PGC Digital Innovation Challenge guidelines were provided by the team and are not listed.

## October 25 Economic Performance Demo

Superseded 26-10-28 for finance: Economy now shows the signed-off COA ledger (2019 to 2025), not a simulated finance demo, and population and business counts are no longer deferred on this page. Only the Tuguegarao City sample wishes remain demonstration data. See "October 28 No Simulated Data and Ledger Table" below.

The user approved a dedicated public Cagayan Economic Performance page with two topics: Government Finances and Community Wishes. The initial implementation uses synthetic Tuguegarao City figures only; the other 28 towns remain selectable with unavailable content. Finance covers completed fiscal-year positions 2021 through 2025, clearly simulated, with Government Revenue/Government Expenses and comparable annual changes. Community Wishes provides curated business-type and fictional specific-business counts in cloud, ranked bars and table views. These counts express wishes, not measured demand.

Population and registered-business counts/history/leading types were explicitly removed from this iteration and remain planned future work. Reason themes, live public-label review/classification, source importing/history storage, spatial analytics and Python predictions are also deferred. The simulation must not alter verified Home figures or expose original live wish text. Keep the existing deferred COA source-collection work separate; implementing this demo does not complete it.

### October 25 Economy UI Polish

The user replaced the initial topic selector with stacked sections: Government Finances followed by Community Wishes. The visible name is Cagayan Economy. Add a town-picker map beside the finance chart, compact spacing, stronger grid lines, icon view switchers and a link from Community Wishes to the existing wish form. Both sections share the selected town, but maintain separate view controls. Population, business counts, forecasts, source imports and live wish publication remain deferred; no extra statistics are invented to fill space.


### October 25 Home and Wishes Simplification

Home now directs residents to share a wish or explore their town's economy, carrying a selected town. Remove the placeholder Business Map/guide task cards and Home protection section. Economy combines curated business types and specific businesses into one ranking with cloud/bars views and actual town/count tooltips; no tables or kind selector. Keep limitations in documentation and date/basis in source details rather than repeated captions/disclaimers. Synthetic data remains labeled, other towns unavailable, and comparative town rankings remain deferred. This does not change wish submission fields, privacy enforcement, or source-collection scope.

## October 28 No Simulated Data and Ledger Table

The user decided that simulated data cannot be the default. Business records are not seeded or shown unless `CAGAYAN_SIMULATED` is true, which stays available for presentations (decisions.md, D37). The public Economy page and Capitol Analytics show real government finances only, plus the Tuguegarao City sample wishes labeled "Sample Data". Executive Summary totals cover all 29 towns; breakdowns beyond totals and barangay population exist for Tuguegarao City only, and the screens say so.

The Government Finances Yearly Summary is one table (item, selected year, year before, change) with one Source line whose links open our stored COA PDF at the cited page. Help is a short sheet in the card header. Layout and rules: `docs/engineering/design.md`, "Government Finances Ledger Table". Metric definitions M19 to M31 are unchanged.
