export type Primitive = string | number | boolean | null | undefined;

export type QueryRecord = Record<string, Primitive | Primitive[] | Record<string, Primitive>>;

export type RouteQueryOptions = {
  query?: QueryRecord;
  mergeQuery?: QueryRecord;
};

export type RouteDefinition<M extends string | readonly string[]> = {
  url: string;
} & (M extends readonly string[] ? { methods: M } : { method: M });

export type RouteFormDefinition<M extends string> = {
  action: string;
  method: M;
};

function toSearchParams(record?: QueryRecord): URLSearchParams {
  const params = new URLSearchParams();
  if (!record) return params;

  const append = (key: string, value: Primitive) => {
    if (value === undefined) return;
    params.append(key, value === null ? '' : String(value));
  };

  for (const [key, value] of Object.entries(record)) {
    if (Array.isArray(value)) {
      for (const v of value) append(key, v as Primitive);
    } else if (value && typeof value === 'object') {
      for (const [innerKey, innerVal] of Object.entries(value)) {
        append(`${key}[${innerKey}]`, innerVal as Primitive);
      }
    } else {
      append(key, value as Primitive);
    }
  }
  return params;
}

export function queryParams(options?: RouteQueryOptions): string {
  const q = options?.mergeQuery ?? options?.query;
  const params = toSearchParams(q);
  const s = params.toString();
  return s ? `?${s}` : '';
}

export function applyUrlDefaults<T extends Record<string, unknown>>(args: T): T {
  // The generated route helpers sometimes call this to normalize/merge defaults.
  // For our usage, we simply return the args unchanged.
  return args;
}
