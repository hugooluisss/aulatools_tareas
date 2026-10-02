import { GoogleCalendarLinkService } from './google-calendar-link.service';

describe('GoogleCalendarLinkService', () => {
  it('builds an encoded Google Calendar template URL', () => {
    const url = new GoogleCalendarLinkService().build(
      'Junta de grupo',
      'Salón 2',
      new Date('2026-10-01T10:00:00Z'),
      new Date('2026-10-01T11:00:00Z'),
    );
    expect(url).toContain('calendar.google.com/calendar/render?action=TEMPLATE');
    expect(url).toContain('text=Junta+de+grupo');
    expect(url).toContain('dates=20261001T100000Z%2F20261001T110000Z');
  });
});
