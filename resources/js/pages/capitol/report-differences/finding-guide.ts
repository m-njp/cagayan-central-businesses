import type { ReportDifferenceFamily } from '@/types/finance';

// One source for the names and explanations on Report Differences: the table, the findings view and the guide.

export const FAMILIES: Record<
    ReportDifferenceFamily,
    { label: string; meaning: string; responsibility: string }
> = {
    report: {
        label: 'Within the Report',
        meaning:
            'The Executive Summary contradicts itself as published: an amount printed with a misplaced comma, an Increase (Decrease) column that does not equal the difference between the two years, a Statement of Financial Position that does not balance, or year headings that do not match the report.',
        responsibility:
            'These are features of the published document. They do not establish which figure is correct, and they are not audit findings on the use of funds.',
    },
    between_reports: {
        label: 'Between Reports',
        meaning:
            'Two Annual Audit Reports show different amounts for the same municipality, line and year. Each report shows the current year and, for comparison, the year before; the comparison column of a later report can differ from the earlier report.',
        responsibility:
            'A later report may correct an earlier one (a restatement). The later report prevails, and the earlier amount is kept as history.',
    },
    reading: {
        label: 'Extraction Limits',
        meaning:
            'The system could not read part of the PDF with certainty: its text is split, damaged or scanned, or its two PDF readers disagree.',
        responsibility:
            'These are limits of the automated reading, not errors by the Commission on Audit or the municipality. The printed page may be correct.',
    },
};

export const FAMILY_LABELS = Object.fromEntries(
    Object.entries(FAMILIES).map(([family, entry]) => [family, entry.label]),
) as Record<ReportDifferenceFamily, string>;

export type FindingEntry = {
    kind: string;
    family: ReportDifferenceFamily;
    title: string;
    checks: string;
    example: string;
    effect: string;
};

export const FINDINGS: FindingEntry[] = [
    {
        kind: 'malformed_amount',
        family: 'report',
        title: 'Amount Printed Incorrectly',
        checks: 'Every amount must be a valid peso amount: commas between thousands and a period before the centavos.',
        example:
            'Santa Praxedes 2016 prints Income as 48,014,545,75, with a comma where the period belongs.',
        effect: 'The row is held back and nothing is recorded for it. A reviewer reads the amount from the page and enters it.',
    },
    {
        kind: 'unreadable_change',
        family: 'report',
        title: 'Change Column Printed Incorrectly',
        checks: 'The Increase (Decrease) and percentage columns must hold valid figures.',
        example: 'Alcala 2014 prints a change of (5732,109.29).',
        effect: 'The amounts for both years are read normally. Sign-off is not affected.',
    },
    {
        kind: 'change_mismatch',
        family: 'report',
        title: 'Change Column Does Not Agree',
        checks: 'For each line, the current-year amount less the prior-year amount must equal the Increase (Decrease) column, within one peso.',
        example:
            'Sanchez Mira 2016: Total Assets of ₱190,816,666.66 less ₱164,777,449.29 is ₱26,039,217.37, but the report shows an increase of ₱21,271,960.61.',
        effect: 'The amounts for both years are recorded as printed. Only the change column is inconsistent, so sign-off is not affected.',
    },
    {
        kind: 'balance_mismatch',
        family: 'report',
        title: 'Financial Position Does Not Balance',
        checks: 'Total Assets must equal Total Liabilities plus Government Equity, within one peso.',
        example:
            'Gattaran 2014, for 2013: Total Assets of ₱172,874,738.39 against Liabilities plus Equity of ₱159,595,296.19, a difference of ₱13,279,442.20.',
        effect: 'Assets for that year are not signed off automatically. A reviewer confirms each amount on the page.',
    },
    {
        kind: 'net_income_mismatch',
        family: 'report',
        title: 'Net Income Does Not Agree',
        checks: 'Income less Expenses must equal the Net Income shown in the report.',
        example:
            'Amulung 2025, for 2024: Income less Expenses is ₱6,256,992.08, but the report shows a net loss of (₱6,588,299.25).',
        effect: 'The system computes Net Income from Income and Expenses and never uses the printed figure, so published figures are not affected.',
    },
    {
        kind: 'conflicting_values',
        family: 'report',
        title: 'Line Reported Twice',
        checks: 'Each line must appear once in the table, or with the same amounts each time.',
        example:
            'Alcala 2014 shows two Total Expenses rows with different amounts.',
        effect: 'Neither row is published until a reviewer decides which applies.',
    },
    {
        kind: 'header_year_mismatch',
        family: 'report',
        title: 'Column Years Do Not Match Report',
        checks: 'The two amount columns must be headed with the report year and the year before.',
        example:
            'A 2017 report whose columns are headed 2016 and 2015. Either the headings or the amounts are wrong; the report alone cannot show which.',
        effect: 'The amounts are not signed off automatically. A reviewer compares them with the neighbouring reports.',
    },
    {
        kind: 'header_year_context_changed',
        family: 'report',
        title: 'Column Years Change Within Table',
        checks: 'Every heading within the Financial Highlights table must name the same two years.',
        example:
            'A table headed 2021 and 2020 whose budget section is headed 2020 and 2019.',
        effect: 'The rows under the later heading are held back unless another report proves which years they belong to.',
    },
    {
        kind: 'header_years_stale',
        family: 'report',
        title: 'Outdated Column Years',
        checks: 'A later heading one year behind the report is compared with the previous report.',
        example:
            'Alcala 2021 heads its budget rows 2020 and 2019. The 2020 report shows ₱197,549,721.31 for 2020, the same as the second column, and the 2022 report shows ₱211,666,466.12 for 2021, the same as the first. The amounts were therefore assigned to 2021 and 2020.',
        effect: 'The amounts are recorded under the years that the other reports prove. A reviewer should confirm them on the page.',
    },
    {
        kind: 'header_years_repeated',
        family: 'report',
        title: 'Same Year on Both Columns',
        checks: 'The two amount columns must carry different years.',
        example: 'Both columns of a 2018 table headed 2018.',
        effect: 'The years are assigned from the report year and are not signed off automatically.',
    },
    {
        kind: 'column_order_changed',
        family: 'report',
        title: 'Column Order Changes',
        checks: 'Every heading must list the years in the same order.',
        example:
            'A table that lists 2025 then 2024, then 2024 then 2025 further down.',
        effect: 'Each row is read in the order of the heading above it. Sign-off is not affected.',
    },
    {
        kind: 'document_year_mismatch',
        family: 'report',
        title: 'Report Year Does Not Match',
        checks: 'The financial highlights must describe the year ended December 31 of the report year.',
        example:
            'A 2019 report whose highlights refer to the year ended December 31, 2018.',
        effect: 'All rows are held back until a reviewer confirms which year the table describes.',
    },
    {
        kind: 'identity_text_conflict',
        family: 'report',
        title: 'Another Municipality Named',
        checks: 'The title, introduction and audit scope paragraph must name the same local government.',
        example:
            'Lasam 2017 introduces Lasam, but its audit scope paragraph says the audit covered the Municipality of Abulug, wording apparently carried over from another report.',
        effect: 'The figures are kept under the municipality named in the title and introduction. Sign-off is not affected.',
    },
    {
        kind: 'minor_difference',
        family: 'report',
        title: 'Difference of One Peso or Less',
        checks: 'Every total, balance and change column is compared to the centavo.',
        example:
            'A report whose Total Assets are ₱0.32 more than Total Liabilities plus Government Equity.',
        effect: 'Listed so reviewers know. A difference of ₱1.00 or less does not hold sign-off; a larger one does.',
    },
    {
        kind: 'duplicate_across_governments',
        family: 'reading',
        title: 'Same Amount Under Two Governments',
        checks: 'No amount may be recorded for the same line and year under two different governments.',
        example:
            'Income of ₱2,540,951.56 recorded for both Enrile and Solana for 2018.',
        effect: 'Both figures wait for a reviewer; neither side is presumed right.',
    },
    {
        kind: 'implausible_change',
        family: 'reading',
        title: 'Change of More Than Three Times',
        checks: 'A figure of ₱1,000,000 or more is compared with the year before.',
        example:
            'Continuing Obligations of ₱31,396,189.10 for 2016 against ₱2,267,803.37 for 2015.',
        effect: 'The figure waits for a reviewer to confirm it against the source. A real change can be this large.',
    },
    {
        kind: 'municipality_mismatch',
        family: 'report',
        title: 'Different Local Government',
        checks: 'The opening pages must name the municipality the report is filed under.',
        example:
            'A report filed under Claveria whose opening pages describe Abulug.',
        effect: 'All figures are held back until the source document is confirmed.',
    },
    {
        kind: 'restated',
        family: 'between_reports',
        title: 'Prior-Year Figure Restated',
        checks: "Each report's prior-year column is compared with the amount the previous report showed for its own year.",
        example:
            'Sanchez Mira Expenses for 2015: the 2015 report shows ₱65,588,576.68, while the 2016 report shows ₱65,372,576.88 for 2015.',
        effect: 'The later report prevails, and the earlier amount is kept as history. A difference of ₱1.00 or less is signed off automatically with the later amount and stays listed here; a larger one waits for a reviewer.',
    },
    {
        kind: 'repeats_previous_year',
        family: 'between_reports',
        title: 'Prior-Year Figure Repeated',
        checks: "A current-year amount must not repeat the previous report's amount for its own year.",
        example:
            'Piat 2015 shows ₱23,361,451.34 for 2015, the same amount the 2014 report shows for 2014.',
        effect: 'The column headings may be reversed or the amount carried forward. The figure is not signed off automatically.',
    },
    {
        kind: 'header_years_swapped',
        family: 'between_reports',
        title: 'Columns Reversed',
        checks: 'Column order is tested against the previous report and the Increase (Decrease) column.',
        example:
            'Gattaran 2024 heads its columns 2024 and 2023 but prints the 2023 amounts first, which both the 2023 report and the change column confirm.',
        effect: 'The amounts are assigned to the years those reports confirm.',
    },
    {
        kind: 'engine_disagreement',
        family: 'reading',
        title: 'PDF Readers Disagree',
        checks: 'Every amount is read by two independent PDF readers, which must find the same amount on the same line and page.',
        example:
            'Rizal 2018 prints its 2017 column cut off (₱69,619,374.5), and the hidden text holds other digits, so the readers disagree.',
        effect: 'The figure is not signed off automatically. A reviewer confirms the amount on the page.',
    },
    {
        kind: 'read_with_fallback_engine',
        family: 'reading',
        title: 'Read by Second PDF Reader',
        checks: 'When the first reader cannot read the whole table, the second reader is used and checked against the first.',
        example:
            'Santo Niño 2019, whose budget rows print their Current label two lines below the amounts.',
        effect: 'Sign-off is not affected when the amounts are confirmed.',
    },
    {
        kind: 'incomplete_row',
        family: 'reading',
        title: 'Row Split in PDF Text',
        checks: "A row's label and amounts must be readable together.",
        example:
            "Alcala 2022's Appropriations Continuing row prints correctly on paper, but the PDF text places its amounts on separate lines.",
        effect: 'The row is held back. A reviewer reads the amounts from the page.',
    },
    {
        kind: 'line_not_found',
        family: 'reading',
        title: 'Line Not Found',
        checks: 'Income, Expenses, Assets, Liabilities and Government Equity are expected in every report.',
        example: 'Baggao 2014, where Government Equity could not be read.',
        effect: 'Nothing is recorded for the line. A reviewer may enter it from the page.',
    },
    {
        kind: 'province_unconfirmed',
        family: 'reading',
        title: 'Province Not Stated',
        checks: 'The opening pages are searched for the Province of Cagayan.',
        example:
            'Most older reports name the municipality without the province.',
        effect: 'For information only. The report was matched by municipality name and its entry in the source register.',
    },
    {
        kind: 'municipality_unconfirmed',
        family: 'reading',
        title: 'Municipality Not Stated',
        checks: 'The opening pages are searched for the municipality the report is filed under.',
        example: 'A report whose opening pages use only "the Municipality".',
        effect: 'For information only. Confirm the municipality on the page.',
    },
    {
        kind: 'header_years_missing',
        family: 'reading',
        title: 'Column Years Not Readable',
        checks: 'The year headings of the two amount columns must be readable.',
        example: 'Iguig 2017, whose year headings are split across lines.',
        effect: 'The years are assigned from the report year and are not signed off automatically.',
    },
    {
        kind: 'no_financial_rows',
        family: 'reading',
        title: 'No Table Rows Read',
        checks: 'The Financial Highlights table must yield at least one row.',
        example: 'A report whose table is an image.',
        effect: 'Nothing is recorded. A reviewer enters the amounts from the page.',
    },
    {
        kind: 'page_without_text',
        family: 'reading',
        title: 'Page Without Text',
        checks: 'Every page must have readable text.',
        example: 'A blank or scanned page.',
        effect: 'For information only, unless the page holds the table.',
    },
];

const BY_KIND = new Map(FINDINGS.map((entry) => [entry.kind, entry]));

export function findingEntry(kind: string): FindingEntry | undefined {
    return BY_KIND.get(kind);
}

export function kindLabel(kind: string): string {
    return (
        BY_KIND.get(kind)?.title ??
        kind
            .replace(/_/g, ' ')
            .replace(/\b\w/g, (letter) => letter.toUpperCase())
    );
}
