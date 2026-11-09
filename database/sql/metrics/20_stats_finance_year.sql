-- stats_finance_year: signed-off government finance series (metrics.md M19 to M27).
-- Only verified figures enter. A missing figure stays missing; nothing is imputed or carried forward.
delete from stats_finance_year;

insert into stats_finance_year (
    municipality_id, fiscal_year, metric, value, definition_variant,
    change, percent_change, comparable, census_year, restated_in, reporting_towns, total_towns, matched_towns
)
with verified as (
    select f.municipality_id, f.fiscal_year, f.line_code as metric, f.basis, f.value, f.definition_variant as variant,
        -- M26: a later report restated the year when it replaced a different earlier figure.
        case when exists (
            select 1 from finance_figures s
            where s.municipality_id = f.municipality_id and s.fiscal_year = f.fiscal_year and s.line_code = f.line_code
                and s.basis = f.basis and s.status = 'superseded' and s.value <> f.value
        ) then d.fiscal_year end as restated_in
    from finance_figures f
    join finance_lines l on l.code = f.line_code
    left join finance_documents d on d.id = f.finance_document_id
    where f.status = 'verified' and l.displayed and l.source <> 'derived'
),
actual as (
    select municipality_id, fiscal_year, metric, value, variant, restated_in from verified where basis = 'actual'
),
pivot as (
    select municipality_id, fiscal_year,
        max(value) filter (where metric = 'performance.revenue') as revenue,
        max(value) filter (where metric = 'performance.expenses') as expenses
    from actual
    group by municipality_id, fiscal_year
),
derived as (
    -- M19: Net Income is Income minus Expenses. Retired 26-11-03: per resident, shares and utilization rates.
    select municipality_id, fiscal_year, 'performance.surplus' as metric, revenue - expenses as value,
        null::varchar as variant, null::smallint as census_year
    from pivot where revenue is not null and expenses is not null
),
series as (
    select municipality_id, fiscal_year, metric, value, variant, null::smallint as census_year, restated_in::smallint from actual
    union all
    select municipality_id, fiscal_year, metric, value, variant, census_year, null from derived
),
ordered as (
    select s.*,
        lag(value) over w as prior_value,
        lag(fiscal_year) over w as prior_year,
        lag(variant) over w as prior_variant
    from series s
    window w as (partition by municipality_id, metric order by fiscal_year)
),
towns as (
    -- M26: change only between consecutive years with the same definition variant; no percent from a zero base.
    select municipality_id, fiscal_year, metric, value, variant, census_year, restated_in, prior_value,
        coalesce(prior_year = fiscal_year - 1 and variant is not distinct from prior_variant, false) as comparable
    from ordered
),
sums as (
    -- M27: Sum of Reporting Towns for peso totals, with change over the towns present in both years.
    select t.fiscal_year, t.metric,
        sum(t.value) as value,
        sum(t.value - t.prior_value) filter (where t.comparable) as change,
        sum(t.prior_value) filter (where t.comparable) as prior_total,
        count(*) as reporting_towns,
        count(*) filter (where t.comparable) as matched_towns
    from towns t
    join finance_lines l on l.code = t.metric
    where l.unit = 'php'
    group by t.fiscal_year, t.metric
)
select municipality_id, fiscal_year, metric, value, variant,
    case when comparable then value - prior_value end,
    -- M26: over the size of the prior value, so a deficit that deepens reads as a fall.
    case when comparable and prior_value <> 0 then round((value - prior_value) / abs(prior_value) * 100, 1) end,
    comparable, census_year, restated_in, null, null, null
from towns
union all
select null, fiscal_year, metric, value, null,
    change,
    case when prior_total <> 0 then round(change / abs(prior_total) * 100, 1) end,
    matched_towns > 0, null, null, reporting_towns, (select count(*) from municipalities), matched_towns
from sums;
