/**
 * Derived from backend App\\Models\\User (alias of App\\Domain\\Staff\\Models\\User)
 * and serialized by StaffResource.
 */
export interface UserModel {
  id: string;
  employee_code: string | null;
  name: string;
  email: string;
  phone: string | null;
  department: string | null;
  position: string | null;
  status: string;
  avatar: string | null;
  hired_at: string | null;
  roles?: string[];
  created_at: string | null;
}
