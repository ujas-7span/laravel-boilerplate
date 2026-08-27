/**
 * Auto-Generated TypeScript Definitions & Type-Safe API Client
 * Generated via: php artisan types:generate
 * Generated at: 2026-08-26T13:32:07+00:00
 */

// ============================================================================
// 1. Core API Response Envelopes
// ============================================================================

export interface ApiResponse<T = unknown> {
  success: boolean;
  message: string;
  data: T;
  meta?: Record<string, unknown>;
}

export interface PaginationMeta {
  total?: number;
  count: number;
  per_page: number;
  current_page?: number;
  total_pages?: number;
  has_more_pages: boolean;
  from?: number | null;
  to?: number | null;
  next_cursor?: string | null;
  prev_cursor?: string | null;
}

export interface PaginatedResponse<T> {
  success: boolean;
  message: string;
  data: T[];
  meta: {
    pagination: PaginationMeta;
    [key: string]: unknown;
  };
}

export interface ApiErrorResponse {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
}

export type MessageResponse = ApiResponse<null>;

// ============================================================================
// 2. Domain Models & Entities
// ============================================================================

export interface PersonalAccessToken {
  id: number;
  tokenable_type: string;
  tokenable_id: number;
  name: string;
  abilities: string[];
  last_used_at: string | null;
  expires_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
  // Default Appends (always present)
  is_verified?: boolean;
  // Dynamic Appends (present when requested via ?append=...)
  initials?: string;
  latest_token_name?: string | null;
  // Eager-loaded Relationships (present when requested via ?include=...)
  tokens?: PersonalAccessToken[];
}

export interface AuthData {
  user: User;
  token: string;
  token_type: 'Bearer';
}

export type AuthResponse = ApiResponse<AuthData>;
export type UserResponse = ApiResponse<User>;
export type UserListResponse = PaginatedResponse<User>;

// ============================================================================
// 3. Request Payloads
// ============================================================================

export interface RegisterPayload {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  device_name?: string;
}

export interface LoginPayload {
  email: string;
  password: string;
  device_name?: string;
}

export interface ForgotPasswordPayload {
  email: string;
}

export interface ResetPasswordPayload {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface UpdateProfilePayload {
  name?: string;
  email?: string;
}

export interface ChangePasswordPayload {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export interface CreateUserPayload {
  name: string;
  email: string;
  password: string;
}

export interface UpdateUserPayload {
  name?: string;
  email?: string;
  password?: string;
}

// ============================================================================
// 4. Type-Safe Query Parameters (GraphQL-like autocomplete in REST)
// ============================================================================

export interface UserFilters {
  name?: string;
  email?: string;
  created_at_after?: string;
  created_at_before?: string;
  created_at_from?: string;
  created_at_to?: string;
  search?: string;
  [key: string]: unknown;
}

export type UserField = keyof User;
export type UserSort =
  | 'id'
  | '-id'
  | 'name'
  | '-name'
  | 'email'
  | '-email'
  | 'created_at'
  | '-created_at'
  | 'updated_at'
  | '-updated_at';

export type UserInclude = 'tokens';
export type UserAppend = 'initials' | 'is_verified' | 'latest_token_name';

export interface QueryOptions<
  TFilter,
  TSort extends string,
  TField extends string,
  TInclude extends string,
  TAppend extends string = string
> {
  search?: string;
  filter?: TFilter;
  sort?: TSort | TSort[];
  fields?: TField[] | Record<string, string[]>;
  include?: TInclude[];
  append?: TAppend[];
  page?: number;
  per_page?: number;
  limit?: number;
  cursor?: string;
}

export type UserQueryOptions = QueryOptions<UserFilters, UserSort, UserField, UserInclude, UserAppend>;

// ============================================================================
// 5. Query String Serializer
// ============================================================================

/**
 * Serializes strongly-typed query options into standard REST URL query parameters.
 */
export function buildQueryParams<
  TFilter extends Record<string, unknown>,
  TSort extends string,
  TField extends string,
  TInclude extends string,
  TAppend extends string
>(options?: QueryOptions<TFilter, TSort, TField, TInclude, TAppend>): string {
  if (!options) return '';

  const params = new URLSearchParams();

  if (options.search) {
    params.set('search', options.search);
  }

  if (options.page) {
    params.set('page', String(options.page));
  }

  if (options.per_page) {
    params.set('per_page', String(options.per_page));
  }

  if (options.limit !== undefined) {
    params.set('limit', String(options.limit));
  }

  if (options.cursor) {
    params.set('cursor', options.cursor);
  }

  if (options.sort) {
    const sortVal = Array.isArray(options.sort) ? options.sort.join(',') : options.sort;
    params.set('sort', sortVal);
  }

  if (options.include && options.include.length > 0) {
    params.set('include', options.include.join(','));
  }

  if (options.append && options.append.length > 0) {
    params.set('append', options.append.join(','));
  }

  // Handle Sparse Fieldsets (both flat array & relation maps)
  if (options.fields) {
    if (Array.isArray(options.fields)) {
      params.set('fields', options.fields.join(','));
    } else {
      for (const [tableOrRelation, columns] of Object.entries(options.fields)) {
        if (Array.isArray(columns) && columns.length > 0) {
          params.set(`fields[${tableOrRelation}]`, columns.join(','));
        }
      }
    }
  }

  // Handle dynamic filters
  if (options.filter) {
    for (const [key, value] of Object.entries(options.filter)) {
      if (value !== undefined && value !== null && value !== '') {
        params.set(`filter[${key}]`, String(value));
      }
    }
  }

  const query = params.toString();
  return query ? `?${query}` : '';
}

// ============================================================================
// 6. Type-Safe Fetch Client (Ready for React, Vue, Next.js, Nuxt, Mobile)
// ============================================================================

export interface ApiClientConfig {
  baseUrl: string;
  getToken?: () => string | null | Promise<string | null>;
}

export function createApiClient(config: ApiClientConfig) {
  const { baseUrl, getToken } = config;

  async function request<TResponse>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<TResponse> {
    const token = getToken ? await getToken() : null;
    const url = `${baseUrl.replace(/\/$/, '')}/${endpoint.replace(/^\//, '')}`;

    const headers: Record<string, string> = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
      ...((options.headers as Record<string, string>) || {}),
    };

    const res = await fetch(url, { ...options, headers });
    const json = await res.json();

    if (!res.ok) {
      throw json as ApiErrorResponse;
    }

    return json as TResponse;
  }

  return {
    auth: {
      register: (payload: RegisterPayload) =>
        request<AuthResponse>('/api/v1/auth/register', {
          method: 'POST',
          body: JSON.stringify(payload),
        }),

      login: (payload: LoginPayload) =>
        request<AuthResponse>('/api/v1/auth/login', {
          method: 'POST',
          body: JSON.stringify(payload),
        }),

      logout: () =>
        request<MessageResponse>('/api/v1/auth/logout', {
          method: 'POST',
        }),

      logoutAll: () =>
        request<MessageResponse>('/api/v1/auth/logout-all', {
          method: 'POST',
        }),

      me: () =>
        request<UserResponse>('/api/v1/auth/me', {
          method: 'GET',
        }),

      updateMe: (payload: UpdateProfilePayload) =>
        request<UserResponse>('/api/v1/auth/me', {
          method: 'PUT',
          body: JSON.stringify(payload),
        }),

      changePassword: (payload: ChangePasswordPayload) =>
        request<MessageResponse>('/api/v1/auth/change-password', {
          method: 'POST',
          body: JSON.stringify(payload),
        }),

      forgotPassword: (payload: ForgotPasswordPayload) =>
        request<MessageResponse>('/api/v1/auth/forgot-password', {
          method: 'POST',
          body: JSON.stringify(payload),
        }),

      resetPassword: (payload: ResetPasswordPayload) =>
        request<MessageResponse>('/api/v1/auth/reset-password', {
          method: 'POST',
          body: JSON.stringify(payload),
        }),
    },

    users: {
      list: (query?: UserQueryOptions) =>
        request<UserListResponse>(`/api/v1/users${buildQueryParams(query)}`, {
          method: 'GET',
        }),

      create: (payload: CreateUserPayload) =>
        request<UserResponse>('/api/v1/users', {
          method: 'POST',
          body: JSON.stringify(payload),
        }),

      get: (id: number | string, query?: Pick<UserQueryOptions, 'include' | 'append' | 'fields'>) =>
        request<UserResponse>(`/api/v1/users/${id}${buildQueryParams(query)}`, {
          method: 'GET',
        }),

      update: (id: number | string, payload: UpdateUserPayload) =>
        request<UserResponse>(`/api/v1/users/${id}`, {
          method: 'PUT',
          body: JSON.stringify(payload),
        }),

      delete: (id: number | string) =>
        request<MessageResponse>(`/api/v1/users/${id}`, {
          method: 'DELETE',
        }),
    },
  };
}