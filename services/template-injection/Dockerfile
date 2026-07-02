FROM python:3.12-slim

WORKDIR /app

RUN pip install --no-cache-dir flask markupsafe

COPY app.py .

ENV PYTHONUNBUFFERED=1
EXPOSE 5000
CMD ["python", "app.py"]
