type Step = {
    id: string;
    label: string;
    detail: string;
    row: number;
    column?: number;
};

export type Workflow = {
    title: string;
    steps: Step[];
    connections: [source: string, target: string][];
};

type Capabilities = {
    canReview: boolean;
    canManageCategories: boolean;
    canAddBusiness: boolean;
};

export function entryWorkflows({
    canReview,
    canManageCategories,
    canAddBusiness,
}: Capabilities) {
    const reviewSteps = (row: number, business = false): Step[] => [
        {
            id: 'pending',
            label: 'Review the Submission',
            detail: canReview
                ? 'Open the source record from Waiting for Review. Verify eligible entries or return them with a reason.'
                : 'The entry stays Pending until an eligible Admin verifies it or returns it with a reason.',
            row,
        },
        {
            id: 'verified',
            label: business ? 'Verify and Publish' : 'Verify the Entry',
            detail: business
                ? 'The Admin approves and publishes the submitted business record.'
                : 'The verified entry appears on the relevant screens.',
            row: row + 1,
        },
        {
            id: 'returned',
            label: 'Correct and Resubmit',
            detail: 'Reopen the source record, address the return reason, and submit it for review again.',
            row,
            column: 1,
        },
    ];
    const reviewConnections: Workflow['connections'] = [
        ['pending', 'verified'],
        ['pending', 'returned'],
        ['returned', 'pending'],
    ];

    return {
        overview: {
            title: 'From Data Entry to Publication',
            steps: [
                {
                    id: 'choose',
                    label: 'Open a Dataset',
                    detail: 'Open Government Finances, Population, or Businesses from Datasets.',
                    row: 0,
                },
                {
                    id: 'submit',
                    label: 'Enter and Submit',
                    detail: 'Make the entry in its source screen and submit it for review.',
                    row: 1,
                },
                ...reviewSteps(2),
                ...(canManageCategories
                    ? [
                          {
                              id: 'save',
                              label: 'Save Category Changes',
                              detail: 'Business category and type changes apply as soon as an authorized Admin saves them.',
                              row: 1,
                              column: 1,
                          },
                      ]
                    : []),
            ],
            connections: [
                ['choose', 'submit'],
                ['submit', 'pending'],
                ...reviewConnections,
                ...(canManageCategories
                    ? ([['choose', 'save']] satisfies Workflow['connections'])
                    : []),
            ],
        },
        finances: {
            title: 'Enter or Upload an Audit Report',
            steps: [
                {
                    id: 'report',
                    label: 'Choose Municipality and Year',
                    detail: 'In Government Finances, choose the municipality and report year.',
                    row: 0,
                },
                {
                    id: 'enter',
                    label: 'Enter Figures Manually',
                    detail: 'Select Enter Report to open the Executive Summary grid. Choose the report source, enter the figures, and select Save for Review.',
                    row: 1,
                },
                {
                    id: 'upload',
                    label: 'Upload and Read a Report',
                    detail: 'Select Upload Report and choose the Executive Summary PDF. Follow the parsing status, then select View Figures. If parsing fails, use Retry Parsing.',
                    row: 1,
                    column: 1,
                },
                ...reviewSteps(2),
            ],
            connections: [
                ['report', 'enter'],
                ['report', 'upload'],
                ['enter', 'pending'],
                ['upload', 'pending'],
                ...reviewConnections,
            ],
        },
        population: {
            title: 'Record or Correct Census Counts',
            steps: [
                {
                    id: 'census',
                    label: 'Choose Municipality and Census Year',
                    detail: 'Open Population, choose a municipality and census year, and enter the counts.',
                    row: 0,
                },
                {
                    id: 'submit',
                    label: 'Save for Review',
                    detail: 'Save the changed counts and their source.',
                    row: 1,
                },
                ...reviewSteps(2),
            ],
            connections: [
                ['census', 'submit'],
                ['submit', 'pending'],
                ...reviewConnections,
            ],
        },
        businesses: {
            title: 'Prepare a Business Record for Publication',
            steps: [
                {
                    id: 'record',
                    label: canAddBusiness
                        ? 'Open or Add Business'
                        : 'Open Business Record',
                    detail: canAddBusiness
                        ? 'Open a draft or returned record, or select Add Business.'
                        : 'Assigned Staff prepare drafts and correct returned records. Reviewers open submitted records from Waiting for Review.',
                    row: 0,
                },
                {
                    id: 'draft',
                    label: 'Save Draft',
                    detail: 'Keep unfinished work in the Draft tab and reopen it when ready.',
                    row: 1,
                    column: 1,
                },
                {
                    id: 'submit',
                    label: 'Submit for Review',
                    detail: 'Send a completed record to an Admin for review.',
                    row: 1,
                },
                ...reviewSteps(2, true),
            ],
            connections: [
                ['record', 'draft'],
                ['record', 'submit'],
                ['draft', 'submit'],
                ['submit', 'pending'],
                ...reviewConnections,
            ],
        },
        categories: {
            title: 'Update Business Categories and Types',
            steps: [
                {
                    id: 'edit',
                    label: 'Add or Edit a Category or Type',
                    detail: 'Open Business Categories and add or edit a category or business type.',
                    row: 0,
                },
                {
                    id: 'save',
                    label: 'Save to Apply Changes',
                    detail: 'Save the changes to apply them immediately.',
                    row: 1,
                },
            ],
            connections: [['edit', 'save']],
        },
    } satisfies Record<string, Workflow>;
}
