-- stats_area_year: the base grain (database.md). Active means new or renewed; sales count only for active years.
-- Only approved registry records count (P15).
delete from stats_area_year;

insert into stats_area_year (
    barangay_id, business_type_id, size_category, year,
    active_count, new_count, renewed_count, not_renewed_count, closed_count,
    declared_sales_total, declared_sales_max_single
)
select
    b.barangay_id, b.business_type_id, b.size_category, y.year,
    count(*) filter (where y.status in ('new', 'renewed')),
    count(*) filter (where y.status = 'new'),
    count(*) filter (where y.status = 'renewed'),
    count(*) filter (where y.status = 'not_renewed'),
    count(*) filter (where y.status = 'closed'),
    coalesce(sum(y.declared_gross_sales) filter (where y.status in ('new', 'renewed')), 0),
    coalesce(max(y.declared_gross_sales) filter (where y.status in ('new', 'renewed')), 0)
from business_years y
join businesses b on b.id = y.business_id
where b.record_status = 'approved'
group by b.barangay_id, b.business_type_id, b.size_category, y.year;
