import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

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

  myTasks(status = 'pending', cycleId?: number) {
    let params = new HttpParams().set('status', status);
    if (cycleId) params = params.set('cycle_id', cycleId);
    return this.http.get<TaskRow[]>(`${this.api}/me/tasks`, {
      params,
      observe: 'response' as const,
    });
  }

  myTask(deliveryId: number): Observable<TaskDetail> {
    return this.http.get<TaskDetail>(`${this.api}/me/tasks/${deliveryId}`);
  }

  comments(deliveryId: number) {
    return this.http.get<Comment[]>(`${this.api}/deliveries/${deliveryId}/comments`);
  }

  addComment(deliveryId: number, body: string): Observable<Comment> {
    return this.http.post<Comment>(`${this.api}/deliveries/${deliveryId}/comments`, {
      body,
    });
  }

  subjects(cycleId?: number) {
    return this.http.get<Subject[]>(`${this.api}/subjects`, {
      params: cycleId ? { cycle_id: cycleId } : {},
    });
  }

  students(subjectId: number, cycleId?: number) {
    return this.http.get<Student[]>(`${this.api}/subjects/${subjectId}/students`, {
      params: cycleId ? { cycle_id: cycleId } : {},
    });
  }

  tasks(subjectId: number, cycleId?: number) {
    return this.http.get<Task[]>(`${this.api}/subjects/${subjectId}/tasks`, {
      params: cycleId ? { cycle_id: cycleId } : {},
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

  deliveries(taskId: number) {
    return this.http.get<DeliveryRow[]>(`${this.api}/tasks/${taskId}/deliveries`);
  }

  markDelivered(deliveryId: number): Observable<unknown> {
    return this.http.put(`${this.api}/deliveries/${deliveryId}/delivered`, {});
  }

  grade(deliveryId: number, grade: number): Observable<unknown> {
    return this.http.put(`${this.api}/deliveries/${deliveryId}/grade`, { grade });
  }
}
