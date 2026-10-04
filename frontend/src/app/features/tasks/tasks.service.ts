import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Page } from '../../core/models/page';

export interface TaskRow {
  task: {
    id: number;
    name: string;
    description: string;
    due_at: string;
    status: string;
    created_at?: string | null;
    updated_at?: string | null;
  };
  subject: { id: number; name: string; teacher_name?: string | null };
  delivery: {
    id: number;
    status: 'pending' | 'delivered' | 'graded' | 'cancelled';
    delivered_at: string | null;
    grade: number | null;
    overdue: boolean;
  };
}

export interface TaskDetail {
  task: TaskRow['task'];
  subject: TaskRow['subject'] & { teacher_name: string | null };
  teacher: { id: number; first_name: string; last_name: string };
  delivery: TaskRow['delivery'];
}

export interface DeliveryHistoryEvent {
  id: number;
  type: string;
  actor: { id: number; name: string; role: string } | null;
  created_at: string;
  payload: Record<string, unknown>;
}

export interface Comment {
  id: number;
  delivery_id: number;
  author: { id: number; first_name: string; last_name: string; role: string };
  body: string;
  created_at: string;
}

export interface Subject {
  id: number;
  name: string;
  status: string;
}

export interface Student {
  id: number;
  first_name: string;
  last_name: string;
  enrollment_number: string;
}

export interface Task {
  id: number;
  subject_id: number;
  name: string;
  description: string;
  due_at: string;
  status: string;
  unread_deliveries?: number;
}

export interface DeliveryRow {
  delivery: TaskRow['delivery'] & { id: number; student_id: number; unread_comments?: number };
  student: Student;
}

export interface TaskOverviewRow {
  delivery_id: number;
  status: 'pending' | 'delivered' | 'graded' | 'cancelled';
  task: { id: number; title: string; description: string; due_at: string };
  student: { id: number; first_name: string; last_name: string };
  subject: { id: number; code: string; name: string };
}

export interface TaskDeliveryStatus {
  code: TaskOverviewRow['status'];
  label: string;
  color: string;
  text_color: string;
}

@Injectable({ providedIn: 'root' })
export class TasksService {
  private readonly http = inject(HttpClient);
  private readonly api = environment.apiUrl;

  myTasks(
    statuses: string[] = [],
    search = '',
    cycleId?: number,
    page = 1,
  ): Observable<Page<TaskRow>> {
    let params = new HttpParams().set('page', page).set('per_page', 20);
    if (statuses.length) params = params.set('status', statuses.join(','));
    if (search.trim()) params = params.set('search', search.trim());
    if (cycleId) params = params.set('cycle_id', cycleId);
    return this.http.get<Page<TaskRow>>(`${this.api}/me/tasks`, {
      params,
    });
  }

  myTask(deliveryId: number): Observable<TaskDetail> {
    return this.http.get<TaskDetail>(`${this.api}/me/tasks/${deliveryId}`);
  }

  deliveryHistory(deliveryId: number): Observable<{ items: DeliveryHistoryEvent[] }> {
    return this.http.get<{ items: DeliveryHistoryEvent[] }>(
      `${this.api}/deliveries/${deliveryId}/history`,
    );
  }

  comments(deliveryId: number, page = 1): Observable<Page<Comment>> {
    return this.http.get<Page<Comment>>(`${this.api}/deliveries/${deliveryId}/comments`, {
      params: { page, per_page: 20 },
    });
  }

  markCommentsRead(deliveryId: number): Observable<unknown> {
    return this.http.post(`${this.api}/deliveries/${deliveryId}/comments/read`, {});
  }

  addComment(deliveryId: number, body: string): Observable<Comment> {
    return this.http.post<Comment>(`${this.api}/deliveries/${deliveryId}/comments`, {
      body,
    });
  }

  subjects(cycleId?: number, page = 1): Observable<Page<Subject>> {
    let params = new HttpParams().set('page', page).set('per_page', 20);
    if (cycleId) params = params.set('cycle_id', cycleId);
    return this.http.get<Page<Subject>>(`${this.api}/subjects`, {
      params,
    });
  }

  students(subjectId: number, cycleId?: number, page = 1): Observable<Page<Student>> {
    let params = new HttpParams().set('page', page).set('per_page', 20);
    if (cycleId) params = params.set('cycle_id', cycleId);
    return this.http.get<Page<Student>>(`${this.api}/subjects/${subjectId}/students`, {
      params,
    });
  }

  tasks(subjectId: number, cycleId?: number, page = 1): Observable<Page<Task>> {
    let params = new HttpParams().set('page', page).set('per_page', 20);
    if (cycleId) params = params.set('cycle_id', cycleId);
    return this.http.get<Page<Task>>(`${this.api}/subjects/${subjectId}/tasks`, {
      params,
    });
  }

  createTask(
    subjectId: number,
    task: Pick<Task, 'name' | 'description' | 'due_at'> & { cycle_id: number },
  ): Observable<Task> {
    return this.http.post<Task>(`${this.api}/subjects/${subjectId}/tasks`, task);
  }

  updateTask(
    taskId: number,
    task: Pick<Task, 'name' | 'description' | 'due_at'>,
  ): Observable<Task> {
    return this.http.put<Task>(`${this.api}/tasks/${taskId}`, task);
  }

  cancelTask(taskId: number): Observable<Task> {
    return this.http.post<Task>(`${this.api}/tasks/${taskId}/cancel`, {});
  }

  deliveries(
    taskId: number,
    page = 1,
    search = '',
    statuses: string[] = [],
  ): Observable<Page<DeliveryRow>> {
    let params = new HttpParams().set('page', page).set('per_page', 20);
    if (search.trim()) params = params.set('search', search.trim());
    if (statuses.length) params = params.set('status', statuses.join(','));
    return this.http.get<Page<DeliveryRow>>(`${this.api}/tasks/${taskId}/deliveries`, {
      params,
    });
  }

  overview(search = '', statuses: string[] = []): Observable<TaskOverviewRow[]> {
    let params = new HttpParams();
    if (search.trim()) params = params.set('search', search.trim());
    if (statuses.length) params = params.set('status', statuses.join(','));
    return this.http.get<TaskOverviewRow[]>(`${this.api}/tasks/overview`, { params });
  }

  deliveryStatuses(): Observable<TaskDeliveryStatus[]> {
    return this.http.get<TaskDeliveryStatus[]>(`${this.api}/tasks/statuses`);
  }

  markDelivered(deliveryId: number): Observable<unknown> {
    return this.http.put(`${this.api}/deliveries/${deliveryId}/delivered`, {});
  }

  markUndelivered(deliveryId: number): Observable<unknown> {
    return this.http.put(`${this.api}/deliveries/${deliveryId}/undelivered`, {});
  }

  grade(deliveryId: number, grade: number): Observable<unknown> {
    return this.http.put(`${this.api}/deliveries/${deliveryId}/grade`, { grade });
  }
}
