import { createAuth } from '../shared/auth';

export const { AuthProvider, useAuth, RequireAuth, GuestOnly } = createAuth('/admin/auth/me', 'admin');
