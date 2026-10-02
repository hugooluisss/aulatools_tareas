import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface Page<T> {
  data: T[];
  meta: { page: number; per_page: number; total: number };
}

export interface TaskRow {
  task: { id: number; name: string; description: string; due_at: string; status: string };
  subject: { id: number; name: string };
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
  subject: TaskRow['subject'];
  teacher: { id: number; first_name: string; last_name: string };
  delivery: TaskRow['delivery'];
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
}

export interface DeliveryRow {
  delivery: TaskRow['delivery'] & { id: number; student_id: number };
  student: Student;
}

@Injectable({ providedIn: 'root' })
export class TasksService {
  private readonly http = inject(HttpClient);
  private readonly api = environment.apiUrl;

  myTasks(status = 'pending'): Observable<Page<TaskRow>> {
    return this.http.get<Page<TaskRow>>(`${this.api}/me/tasks`, {
      params: new HttpParams().set('status', status),
    });
  }

  myTask(deliveryId: number): Observable<{ data: TaskDetail }> {
    return this.http.get<{ data: TaskDetail }>(`${this.api}/me/tasks/${deliveryId}`);
  }

  comments(deliveryId: number): Observable<Page<Comment>> {
    return this.http.get<Page<Comment>>(`${this.api}/deliveries/${deliveryId}/comments`);
  }

  addComment(deliveryId: number, body: string): Observable<{ data: Comment }> {
    return this.http.post<{ data: Comment }>(`${this.api}/deliveries/${deliveryId}/comments`, {
      body,
    });
  }

  subjects(): Observable<Page<Subject>> {
    return this.http.get<Page<Subject>>(`${this.api}/subjects`);
  }

  students(subjectId: number): Observable<Page<Student>> {
    return this.http.get<Page<Student>>(`${this.api}/subjects/${subjectId}/students`);
  }

  tasks(subjectId: number): Observable<Page<Task>> {
    return this.http.get<Page<Task>>(`${this.api}/subjects/${subjectId}/tasks`);
  }

  createTask(
    subjectId: number,
    task: Pick<Task, 'name' | 'description' | 'due_at'>,
  ): Observable<{ data: Task }> {
    return this.http.post<{ data: Task }>(`${this.api}/subjects/${subjectId}/tasks`, task);
  }

  updateTask(
    taskId: number,
    task: Pick<Task, 'name' | 'description' | 'due_at'>,
  ): Observable<{ data: Task }> {
    return this.http.put<{ data: Task }>(`${this.api}/tasks/${taskId}`, task);
  }

  cancelTask(taskId: number): Observable<{ data: Task }> {
    return this.http.post<{ data: Task }>(`${this.api}/tasks/${taskId}/cancel`, {});
  }

  deliveries(taskId: number): Observable<Page<DeliveryRow>> {
    return this.http.get<Page<DeliveryRow>>(`${this.api}/tasks/${taskId}/deliveries`);
  }

  markDelivered(deliveryId: number): Observable<unknown> {
    return this.http.put(`${this.api}/deliveries/${deliveryId}/delivered`, {});
  }

  grade(deliveryId: number, grade: number): Observable<unknown> {
    return this.http.put(`${this.api}/deliveries/${deliveryId}/grade`, { grade });
  }
}
